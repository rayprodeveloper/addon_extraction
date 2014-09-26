<?php 
/**
 * Tracking Module
 * @author Niki Rohani
 * 
 */
 


if (!class_exists('Interspire_Addons', false)) {
    require_once(dirname(dirname(__FILE__)) . '/interspire_addons.php');
}

define("ADDON_MTA_DEFAULT_SMTP_PORT", 25);


define ("ADDON_COMMENT_BEGIN", "/* ADDON  %%file%% %%line%% BEGIN */");
define ("ADDON_COMMENT_END", "/* ADDON  %%file%% %%line%% END */");
require_once (dirname(__FILE__) . '/language/language.php');

class Addons_extractor extends Interspire_Addons {
    public $addon_name="extractor";
    public $install_ = array();
    
    
    
    function Install() {
        // Permet la connexion à la base
        $this -> Db = IEM::getDatabase();
        $this -> Db -> Connect();
        
        
        // Permet une requete
        // $quer =  $this -> Db -> Query ("");
        // $this -> Db -> Fetch ($quer);
        
         $quer =  $this -> Db -> Query ("CREATE TABLE IF NOT EXISTS ". SENDSTUDIO_TABLEPREFIX ."addons_extraction_settings (hours int(11), path varchar(255), maxProcess int(8))");
         if ($quer == false){
         	throw new Exception("impossible de creer la table email_addons_extraction_settings");
         }
         $quer =  $this -> Db -> Query ("CREATE TABLE IF NOT EXISTS ". SENDSTUDIO_TABLEPREFIX ."addons_extraction_historique (id int(11) NOT NULL AUTO_INCREMENT, timeStarted int(18), ,workStatus varchar(255),campagneName varchar(255), lastTimeUpdate int (11), type varchar (255), campaignId int (11), PRIMARY KEY (id))");
         if ($quer == false){
         	throw new Exception("impossible de creer la table email_addons_extraction_historique");
         }	
         
         $quer = $this -> Db ->  Query ("SELECT * from ". SENDSTUDIO_TABLEPREFIX ."addons_extraction_settings");
         $this -> Db -> Fetch ($quer);
         
        // echo "This is some text" > randomtext.txt
        
        
        // Modification des fichier
          $this -> clean();
        $install = $this -> installFile ();
        
        
        
        ////////////////////////////////////////////////////////////
        $this->enabled = true;
        $this->configured = true;
        $this->settings = $this->default_settings;
        
        if ($install == false)
            throw new Exception ("Installation impossible");
        
       try {
            $status = parent::Install();
        } catch (Interspire_Addons_Exception $e) {
            throw new Exception("Unable to install addon {$this->GetId()}" . $e->getMessage());
        }
        ///////////////////////////////////////////////////////////////////
        
        return true;
    }
    
    
    /**
     * ProcÃ¨de Ã  la dÃ©sinstallation du module
     * @see Addons_tracking::clean_backup();
     * @see Addons_tracking::clean();
     * @throws Exception
     * @return boolean
     */
    function Uninstall() {
        
        
        // On récupère les anciens fichiers
        $old = scandir (IEM_ADDONS_PATH . '/' . $this -> addon_name . '/backup');
        if ($old == false)
            throw new Exception ("Impossible de dÃ©sinstaller l'addon");
      
        
       
       foreach ($old as $backup)
           if ($backup != '.' && $backup != "..")    {       
               $file = file_get_contents (IEM_ADDONS_PATH . "/' . $this -> addon_name . '/backup/" . $backup);
               
               if ($file == false)
                   throw new Exception ("Impossible de desinstaller l'addon");
              
            if (!file_put_contents (IEM_ADDONS_PATH . "/../" . str_replace ('%', '/', $backup), $file))
                throw new Exception ("Impossible de dÃ©sinstaller l'addon ");
            
          
           }

           
          
           $this -> clean_backup ();
           $this -> clean();
           
        try {
            $status = parent::Uninstall();
        } catch (Interspire_Addons_Exception $e) {
            throw new Exception("Unable to install addon {$this->GetId()}" . $e->getMessage());
        }
        
        
        return true;
    }
    
    
    
    
    
    function process_file() {
        foreach ($this -> install_ as $dirfile) {
            $file = file_get_contents (IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install/" . $dirfile );
            if (!file_put_contents (IEM_ADDONS_PATH . "/../" . str_replace ('%', '/', $dirfile), $file))
                throw new Exception ('Erreur, fichier possible corrompue ');
        }
       
    }
    
    
    /**
     * Fonction permettant la modification des fichiers
     * @return boolean
     */
    function installFile () {
            // Ici on modifie les fichier   
            // Exemple
            // On modifie le fichier X.php contenue dans le dossier INTERSPIRE/admin/Y/
            //
            // $opt = array (LIGNE DU FICHIER => ' CONTENUE ',
            //              "" ""             => "" "");
            //
            // Le tableau permet de donner les lignes que l'on souhaite modifier, ou où l'on souhaite ajouter
            //
            // $install = $this -> add ($opt, 'X.php', 'Y/', true);
            // 
            // On modifie les lignes du fichier X.php, en remplacant ces lignes la car on a mis à true sinon on met à false et on ajoute une ligne au fichier
            // Si on ajoute une ligne au fichier il faut bien calculer les lignes pour les suivant
            //
            //
            // Si on veux supprimer des lignes
            // 
            // Exemple
            //
            //
            // $opt = array (LIGNE DEBUT, LIGNE FIN);
            //
            //
            // Le tableau détermine les ligne à supprimer
            // $install = $this -> del ($opt, 'X.php', 'Y/');
            //
            // On a supprimé les lignes [LDEBUT,LFIN] de INTERSPIRE / admin / Y / X.php, la suppression ne fait que commenter ces lignes, ainsi pas besoin de calculer si on veux ajouter du contenue
            //
            //
            //
            
        
        
            // Une fois les modifications effectué on effectue les changements
          $this -> process_file();
           return true;
    }
    
    
    function Action_ () {
        $option = "";
         $this -> Db = IEM::getDatabase();
        $this -> Db -> Connect();
        $list = $this -> Db -> Query ('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'addon_tracking ');
        $listselect = "<table style='border: 1px solid' width='100%'>";
        $listselect = $listselect . "<tr style='border : 1px dotted'> <td style='border : 1px dotted'> Mta </td> <td> Lien </td> </tr> ";
        while ($listid = $this -> Db -> Fetch ($list))
            $listselect = $listselect . "<tr> <td> " . $listid['mta'] .  " </td> <td> " . $listid['link'] . " </td> <td> <form  method='post' action='" .$this->settings_url . "&SubAction=SaveSettings' target='_parent'   > <input type = 'hidden' name = 'id' value = '" . $listid ['id'] . "'/> <input type = 'submit' value = 'X'/>  </form> </tr> ";
        $listselest = $listselect . "</table>";
    
        $this -> Db -> Disconnect();
        $this -> template_system =  GetTemplateSystem(IEM_ADDONS_PATH . '/' . $this -> addon_name . '/templates/');
    //  $this -> template_system -> Assign ('file', $option);
        $this -> template_system -> Assign ('save', $this -> settings_url);
        $this -> template_system -> Assign ('listid', $listselect);
        return $this -> template_system -> ParseTemplate ('settings', true);
    
    }
    
    
    
    public function SaveSettings()
    {
        $this -> db = IEM::getDatabase();
        $this -> db -> Connect();
        if (isset ($_POST ['mta'])) {
        $query = $this -> db -> Query ('INSERT INTO ' . SENDSTUDIO_TABLEPREFIX . 'addon_tracking (mta, link) VALUES ("' . $_POST ['mta'] . '", "' . $_POST ['link'] . '") ');
        if ($query)
            return true;
        return false;
        }
        else
        {
            $this -> db -> Query ('DELETE FROM ' . SENDSTUDIO_TABLEPREFIX . 'addon_tracking WHERE id = ' . $_POST ['id'] . ' ');
            return true;
        }
    }
    
    public function delete_link()
    {
        
    }
     
    /** 
     * Permet d'ajouter une ligne Ã  un fichier
     * @param array $opt Tableau d'option (ligne => insertion)
     * @param string $file Nom de fichier
     * @param string $dir Chemin
     * @param bool $replace Si defini Ã  true les lignes sont remplacÃ©es
     * @throws Exception Impossible d'ouvrir un fichier
     * @return boolean true si la fonction est executÃ© avec succÃ¨s
     */
    function add ($opt, $file, $dir, $replace = false, $comment = true) {
        $handle = false;
        if (file_exists ( IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install/" . str_replace ('/', '%', $dir) . $file ))
            if ( ($handle = @fopen(IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install/" . str_replace ('/', '%', $dir) . $file, "r+")) == false)
                throw new Exception  ("Impossible d'ouvrir " . IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install/" . str_replace ('/', '%', $dir) . $file);
                
         if ($handle == false) {
            $file_old = file_get_contents (IEM_ADDONS_PATH . "/../" . $dir . $file);
            if ($file_old == false)
                throw new Exception  ("Impossible d'ouvrir " . IEM_ADDONS_PATH . "/../" . $dir . $file);
            $file_old = file_put_contents (IEM_ADDONS_PATH . "/' . $this -> addon_name . '/backup/" . str_replace ('/', '%', $dir) . $file, $file_old);
            if ($file_old == false)
                throw new Exception ("Impossible d'ecrire " . IEM_ADDONS_PATH . "/' . $this -> addon_name . '/backup/" . $file);
        
         }
        
        
        
       
        if ($handle == false) {
        $handle = @fopen(IEM_ADDONS_PATH . "/../" . $dir . $file, "r+");
        if ($handle == false)
            throw new Exception ("Imposssible d'ouvrir " . IEM_ADDONS_PATH . "/../"     . $dir . $file);
        }
        
        if ($handle) {
            $i = 1;
            $filecontent = '';
            $begin = str_replace ("%%file%%", $file, ADDON_COMMENT_BEGIN);
            $end = str_replace ("%%file%%", $file, ADDON_COMMENT_END);
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                if (isset ($opt [$i]) ) {
                    
                       if ($replace == false)
                        $buffer = (($comment) ?  str_replace ("%%line%%", $i, $begin) : '' ) . $opt [$i] . PHP_EOL . $buffer;
                       else
                          $buffer =   (($comment) ?    str_replace ("%%line%%", $i, $begin) : '' ) .  $opt [$i] . PHP_EOL;
                }
                $filecontent = $filecontent . $buffer;
                $i = $i + 1;
                
            }
            
            fclose($handle);
            
             $this -> install_ [$dir . $file] = str_replace ('/', '%',$dir) . $file;
            
         if (!file_put_contents (IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install/" . str_replace ('/', '%', $dir) .  $file, $filecontent))
             throw new Exception ("Impossible d'Ã©crire dans le dossier ". IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install");   
         
            
        }
        
        
        return true;
    }
    
    
    /**
     * Permet de supprimer des lignes d'un fichier 
     * @param array $opt Option      $opt = [ LigneDebut, LigneFin
     * @param string $file Nom fichier
     * @param string $dir Chemin
     * @throws Exception Si impossible d'ouvrir un fichier
     * @return boolean retourne true si la fonction est executÃ© sans problÃ¨me
     */
    function del ($opt, $file, $dir) {
        $handle = false;
        if (file_exists ( IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install/" . str_replace ('/', '%', $dir) . $file))
        if ( ($handle = @fopen(IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install/" . str_replace ('/', '%', $dir) . $file, "r+")) == false)
            throw new Exception  ("Impossible d'ouvrir " . IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install/" . str_replace ('/', '%', $dir) . $file);
        
        if ($handle == false) {
            $file_old = file_get_contents (IEM_ADDONS_PATH . "/../" . $dir . $file);
            if ($file_old == false)
                throw new Exception  ("Impossible d'ouvrir " . IEM_ADDONS_PATH . "/../" . $dir . $file);
            $file_old = file_put_contents (IEM_ADDONS_PATH . "/' . $this -> addon_name . '/backup/" . str_replace ('/', '%', $dir) . $file, $file_old);
            if ($file_old == false)
                throw new Exception ("Impossible d'ecrire " . IEM_ADDONS_PATH . "/' . $this -> addon_name . '/backup/" . $file);
        
        }
        
        
        
         
        if ($handle == false) {
            $handle = @fopen(IEM_ADDONS_PATH . "/../" . $dir . $file, "r+");
            if ($handle == false)
                throw new Exception ("Imposssible d'ouvrir " . IEM_ADDONS_PATH . "/../"     . $dir . $file);
        } 
      
        if ($handle) {
            $i = 1;
            $filecontent = '';
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                if ($i >= ($opt [0]) && $i <= $opt [1] ) {
                    $filecontent = $filecontent . "/* ADDON TRACKING SUPPRESS LINE $opt[0] $opt[1] */ " . PHP_EOL ;
                }
                else
                $filecontent = $filecontent . $buffer;
                $i = $i + 1;
    
            }
    
            fclose($handle);
    
            $this -> install_ [$dir . $file] = str_replace ('/', '%',$dir) . $file;
    
            if (!file_put_contents (IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install/" . str_replace ('/', '%', $dir) .  $file, $filecontent))
                throw new Exception ("Impossible d'Ã©crire dans le dossier ". IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install");
             
    
        }
    
    
        return true;
    }
    
    
    /**
     * Permet de récupérer récupérer l'api de l'addon afin d'appeler des fonctions.
     * @param unknown $null
     * @return false si n'arrive pas à récup| ADDON_API
     */
    protected function GetApi ($null)
    {
        
        $path = IEM_ADDONS_PATH .  '/' . $this -> addon_name . '/api/' .
                   '' . $this -> addon_name . '.php';
        
        if (! is_file($path))
        {
            return false;
        }
        require_once $path;
        $class =  '' . $this -> addon_name . '' . '_API';
        
        $api = new $class();
        $api->template_system = $this->template_system;
        $api->addon_name = $this -> addon_name;
        return $api;
    }
    
    /**
     * Supprime le rÃ©pertoire install qui contient les fichier post installation
     * @throws Exception Si un fichier refuse de se supprimer
     */
    protected function clean () {
        $dir = scandir (IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install");
        foreach ($dir as $file) {
            if ($file != "." && $file != "..")
            if (!unlink (IEM_ADDONS_PATH . "/' . $this -> addon_name . '/install/"  . $file))
                throw new Exception ("Impossible de supprimer le contenue du repertoire install");
        }
    }
    
    
    /**
     * Supprime le rÃ©pertoire backup qui contient les fichier pré installation
     * @throws Exception Si un fichier refuse
     */
    protected function clean_backup () {
        $dir = scandir (IEM_ADDONS_PATH . "/' . $this -> addon_name . '/backup/");
        foreach ($dir as $file) {
            if ($file != "." && $file != "..")
            if (!unlink (IEM_ADDONS_PATH . "/' . $this -> addon_name . '/backup/" . $file))
                throw new Exception ("Impossible de supprimer le contenue du repertoire backup " . $file);
        }
    }
    
}

?>