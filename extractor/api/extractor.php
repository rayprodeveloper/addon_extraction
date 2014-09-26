<?php 




class extractor_Api {

    
    
    public $db;
    public $template_system;
    public $addon_name;
    
   	public $job;
    function __construct() {
    	$this -> addon_name = "extractor";
        $this -> template_system =  GetTemplateSystem(IEM_ADDONS_PATH . '/' . $this -> addon_name . '/templates/');
        $this -> db = IEM::getDatabase();
        $this -> job = array ('open', 'bounce', 'unsub');
    }
    
    
    /**
     * 
     */
    function fonction() {
        
        // Connexion à la base de donnée
        $link = $this -> db-> Connect();
        
        $linkq = $this -> db -> Query ('');
        
        
        while ($link = $this -> db -> Fetch ($linkq)) {
        	
        }
        
        $this -> db -> FreeResult($link);
        $this -> db -> Disconnect();
        
        
        
            // Permet d'assigner les variable au template
            // {$tracking} => $str
            
          $this -> template_system ->Assign ('tracking', $str);
          
            // Permet d'ajouter à la variable global %%ADDON_TRACKING%% le template iem_tracking_send
            // Il suffit d'ajouter %%ADDON_TRACKING%% au fichier souhaité pour afficher
            // L'ajout se fait à l'installation
          $GLOBALS ['ADDON_TRACKING'] =  $this -> template_system ->ParseTemplate ('iem_tracking_send', true);
          
          
    }
   
   
    
    
    
    
    function cron() {
    	
    	$this -> db -> Connect();
    	
    	// recupere les process qui sont en attente et en cours depuis plus d'une heure
    	$jobs = $this -> db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX .   'addons_extractor_historique WHERE workStatus = "w" OR (workStatus = "i" AND lastTimeUpdate <  ' . time() - 3600 . ')');
    	
    	if (!$jobs) {
    		echo 'Erreur lors de la récupération du jobs';
    		exit;
    	}
    	
    	$jobs = $this -> db -> Fetch ($jobs);
    	
    
    	if ($jobs) {
    		// On reprend le job arreter
				$this -> Process ($jobs);
    		exit;
    	}
    	
    	// récupere le nombre de process en cours 
    	$jobs = $this -> db -> Query ('SELECT COUNT(*) FROM ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_historique WHERE workStatus = "i" OR workStatus = "w"  ');
    	
    	$jobs = $this -> db -> Fetch ($jobs);
    	
    	// recupere les settings de l'addon
    	$settings = $this -> db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_settings ');
    	
    	$settings = $this -> db -> Fetch ($settings);
    	
    	// si le nombre de process en cours est superieur au nombre max de process quitter le script
    	if ($jobs)
    		if ($jobs ['COUNT(*)'] > $settings ['maxProcess']) {
    			echo 'Trop de job en cour';
    			exit;
    		} 
    	
    	
    	// recupere toutes les campagne fini depuis plus de 72h et non check 
    	$campaign = $this -> db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'stats_newsletters WHERE  finishtime < ' . time() - $settings["hours"]*3600 . ' AND check != "check" ');
    	
    	if (!$campaign) {
    		echo 'Erreur récupération';
    		exit;
    	}
    	
    	$campaign = $this -> db -> Fetch ($campaign);
    	
    	
    	$this -> Job ($campaign);
    	
    	
    	$this -> db -> Disconnect();
    	
    }
    
    
    
    private function Process ($campaign) {
    	
    	echo 'Debut du job : ' . $campaign ['id'];
    	$res = $this -> db -> Query ('UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_historique SET workStatus = "i" AND lastTimeUpdate = ' . time() . ' WHERE id = ' . $campaign ['id'] . ' ');
    	if (!$res) {
    		echo 'Erreur lors de la mise à jour du status';
    		exit;
    	}
    	echo PHP_EOL;
    	echo "Création du fichier";
    	
    	$settings = $this -> db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_settings ');
    	$settings = $this -> db -> Fetch ($settings);
    	
    	if (!$settings) {
    		echo ' Problème base ';
    		exit;
    	}
   
    	
    	if ($campaign ['type'] == 'open')
    		$this -> ProcessOpen ($campaign, $settings);
    	else
    		exit;
    	
    	
		
    	
    	
    	$this -> db -> Query ('UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_historique SET workStatus = "t" WHERE id = ' . $campaign ['id'] . ' ');
    	
    	
    	
    	
    }
    
    
    private function ProcessOpen ($campaign, $settings) {
    	$fp = fopen ($settings ['path'] . '/' . $campaign ['type'] . '_' . $campaign ['jobid'] . '_' . $campaign ['campaignid'], 'a');
    	
    	$open = $this -> db -> Query ('SELECT * FROM   ' . SENDSTUDIO_TABLEPREFIX . 'stats_emailopens WHERE opentime > ' . $campaign ['lastTimeUpdate'] . '	');
    	if (!$open) {
    		echo 'Erreur base';
    		exit;
    	}
    	
    	while ($write = $this -> db -> Fetch ($open))   {
    		$write = $this -> db -> Query ('SELECT emailaddress FROM ' . SENDSTUDIO_TABLEPREFIX . 'list_subscribers WHERE subscriberid = ' . $write ['subscriberid'] . ' ');
    		$write = $this -> db -> Fetch ($write);
    		echo 'ecriture ' . $write ['emailaddress'] . ' ' . PHP_EOL;
    		fwrite ($fp, $write ['emailaddress'] . ' ' . PHP_EOL);
    		
    		$upd = $this -> db -> Query ('UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_historique SET lastUpdateTime = ' . time () . ' WHERE id = ' . $campaign ['id']   . ' ');
    		if (!$upd) {
    			echo 'ERREUR ECRITURE DANS LA BASE ! FICHIER COROMPU';
    			exit;
    		}
    	}
    	
    	
    	echo 'Fin de job';
    	
    	
    }
    
    
    private function Job ($campaign) {
    	
    	
    	
    	
    	$this -> db -> StartTransaction();
		
    	// On essaie de dire que le job en cour est en train d'être update
    	$update = $this -> db -> Query ('UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'stats_newsletters WHERE jobid = ' . $campaign ['jobid'] . ' SET check = "check" ');
		if (!$update) {
			$this -> db -> RollBackTransaction ();
			echo 'Erreur lors de la mise à jour de la table de job ';
			exit;
		}
		// recupere les campagnes
		$campaigndetail = $this -> db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'newsletters WHERE newsletterid = ' . $campaign ['newsletterid'] . ' ');
		if (!$campaigndetail) {
			$this -> db -> RollBackTransaction ();
			echo 'Erreur lors la recherche ';
			exit;
		}
		
		$campaigndetail = $this -> db -> Fetch ($campaigndetail);
		
		if (!$campaigndetail) {
			$campaigndetail = array ('name' => 'inconnu');
		}
		
		// On crée les jobs
		foreach ($this -> job as $type) {
			echo 'Création du job : ' . $campaigndetail ['name'];
			$job = $this -> db -> Query ('INSERT INTO ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_historique (timeStarted, workStatus, campagneName, lastTimeUpdate, type, campaignid, jobid) VALUES (' . time() . ', "w", "' . $campaigndetail ['name'] . ' ' . date ('d-M-Y', $campaign ['starttime']) . ']", 0, " ' . $type . ' ", ' . $campaign ['newsletterid'] . ', ' . $campaign ['jobid'] . ') ');
			if (!$job) {
				$this -> db -> RollBackTransaction ();
				exit;
			}
			echo ' ..... Création fait';
		}
		
		echo 'Jobs waiting';
		
		
		$this -> db -> CommitTransaction();
		
		
    }
}

?>