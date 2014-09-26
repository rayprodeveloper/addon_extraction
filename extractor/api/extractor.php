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
    
   
   
}

?>