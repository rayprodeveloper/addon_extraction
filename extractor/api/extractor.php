<?php 




class extractor_Api {

    
    
    public $db;
    public $template_system;
    public $addon_name;
    function __construct() {
    	$this -> addon_name = "extractor";
        $this -> template_system =  GetTemplateSystem(IEM_ADDONS_PATH . '/' . $this -> addon_name . '/templates/');
        $this -> db = IEM::getDatabase();
    }
    
    
    /**
     * 
     */
    function fonction() {
        
        // Connexion � la base de donn�e
        $link = $this -> db-> Connect();
        
        $linkq = $this -> db -> Query ('');
        
        
        while ($link = $this -> db -> Fetch ($linkq)) {
        	
        }
        
        $this -> db -> FreeResult($link);
        $this -> db -> Disconnect();
        
        
        
            // Permet d'assigner les variable au template
            // {$tracking} => $str
            
          $this -> template_system ->Assign ('tracking', $str);
          
            // Permet d'ajouter � la variable global %%ADDON_TRACKING%% le template iem_tracking_send
            // Il suffit d'ajouter %%ADDON_TRACKING%% au fichier souhait� pour afficher
            // L'ajout se fait � l'installation
          $GLOBALS ['ADDON_TRACKING'] =  $this -> template_system ->ParseTemplate ('iem_tracking_send', true);
          
          
    }
   
   
    
    
    
    
    function cron() {
    	
    	$this -> db -> Connect();
    	
    	// recupere les process qui sont en attente et en cours depuis plus d'une heure
    	$jobs = $this -> db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX .   'addons_extractor_historique WHERE workStatus = "w" OR (workStatus = "i" AND lastTimeUpdate <  ' . time() - 3600 . ')');
    	
    	if (!$jobs) {
    		echo 'Erreur lors de la r�cup�ration du jobs';
    		exit;
    	}
    	
    	$jobs = $this -> db -> Fetch ($jobs);
    	
    
    	if ($jobs) {
    		// On reprend le job arreter
				$this -> Process ($jobs);
    		exit;
    	}
    	
    	// r�cupere le nombre de process en cours 
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
    		echo 'Erreur r�cup�ration';
    		exit;
    	}
    	
    	$campaign = $this -> db -> Fetch ($campaign);
    	
    	
    	$this -> Job ($campaign);
    	
    	
    	$this -> db -> Disconnect();
    	
    }
    
    
    
    private function Process ($campaign) {
    	
    }
    
    
    
    private function Job ($campaign) {
    	// Definition des jobs
    	$array = array ('open', 'bounce', 'unsub');
    	
    	
    	
    	
    	$this -> db -> StartTransaction();
		
    	// On essaie de dire que le job en cour est en train d'�tre update
    	$update = $this -> db -> Query ('UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'stats_newsletters WHERE jobid = ' . $campaign ['jobid'] . ' SET check = "check" ');
		if (!$update) {
			$this -> db -> RollBackTransaction ();
			echo 'Erreur lors de la mise � jour de la table de job ';
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
		
		// On cr�e les jobs
		foreach ($array as $type) {
			echo 'Cr�ation du job : ' . $campaigndetail ['name'];
			$job = $this -> db -> Query ('INSERT INTO ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_historique (timeStarted, workStatus, campagneName, lastTimeUpdate, type, campaignId) VALUES (' . time() . ', "w", "' . $campaigndetail ['name'] . ' ' . date ('d-M-Y', $campaign ['starttime']) . ']", 0, " ' . $type . ' ", ' . $campaign ['newsletterid'] . ') ');
			if (!$job) {
				$this -> db -> RollBackTransaction ();
				exit;
			}
			echo ' ..... Cr�ation fait';
		}
		
		echo 'Jobs waiting';
		
		
		$this -> db -> CommitTransaction();
		
		
    }
}

?>