<?php 




class nom_Api {

    
    
    public $db;
    public $template_system;
    public $addon_name;
    function __construct() {
        $this -> template_system =  GetTemplateSystem(IEM_ADDONS_PATH . '/' . $this -> addon_name . '/templates/');
        $this -> db = IEM::getDatabase();
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
    	
    	
    	$jobs = $this -> db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX .   'addons_extractor_historique WHERE workStatus = "w" OR (workStatus = "i" AND lastTimeUpdate <  ' . time() - 3600 . ')');
    	
    	if (!$jobs) {
    		echo 'Erreur lors de la récupération du jobs';
    		exit;
    	}
    	
    	$jobs = $this -> db -> Fetch ($jobs);
    	
    	
    	if ($jobs) {
    		// On reprend le job

    		exit;
    	}
    	
    	
    	$jobs = $this -> db -> Query ('SELECT COUNT(*) FROM ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_historique WHERE workStatus = "i" OR workStatus = "w"  ');
    	
    	$jobs = $this -> db -> Fetch ($jobs);
    	
    	$settings = $this -> db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_settings ');
    	
    	$settings = $this -> db -> Fetch ($settings);
    	
    	
    	if ($jobs)
    		if ($jobs ['COUNT(*)'] > $settings ['maxProcess']) {
    			echo 'Trop de job en cour';
    			exit;
    		} 
    	
    	$campaign = $this -> db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'jobs WHERE jobtype = "send" AND jobstatus = "c" AND lastupdatetime < ' . time() - (3600 * 72) . ' AND check != "check" ');
    	
    	if (!$campaign) {
    		echo 'Erreur récupération';
    		exit;
    	}
    	
    	$campaign = $this -> db -> Fetch ($campaign);
    	
    	
    	$this -> Job ($campaign);
    	
    	
    }
    
    
    
    private function Process ($campaign) {
    	
    }
    
    
    
    private function Job ($campaign) {
    	// Definition des jobs
    	$array = array ('open', 'bounce', 'unsub');
    	
    	
    	
    	
    	$this -> db -> StartTransaction();
		
    	// On essaie de dire que le job en cour est en train d'être update
    	$update = $this -> db -> Query ('UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'jobs WHERE jobid = ' . $campaign ['jobid'] . ' ');
		if (!$update) {
			$this -> db -> RollBackTransaction ();
			echo 'Erreur lors de la mise à jour de la table de job ';
			exit;
		}
		
		$campaigndetail = $this -> db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'newsletters WHERE newsletterid = ' . $campaign ['fkid'] . ' ');
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
		foreach ($array as $type) {
			echo 'Création du job : ' . $campaigndetail ['name'];
			$job = $this -> db -> Query ('INSERT INTO ' . SENDSTUDIO_TABLEPREFIX . 'addons_extractor_historique (timeStarted, workStatus, campagneName, lastTimeUpdate, type, campaignId) VALUES (' . time() . ', "w", "' . $campaigndetail ['name'] . ' ' . date ('d-M-Y', $campaign ['jobtime']) . ']", 0, " ' . $type . ' ", ' . $campaign ['fkid'] . ') ');
			if (!$job) {
				$this -> db -> RollBackTransaction ();
				exit;
			}
			echo ' ..... Création fait';
		}
		
		echo 'Jobs waiting';
		
		
		
    }
}

?>