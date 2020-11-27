<?php

class ModelExtensionExtractorModulesDashboard extends Model {

    /**
     * Extract all the changes on the site
     * @return mixed
     */
    public function getModification(){
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "modification`")->rows;
    }

    /**
     * Extract the type
     * @param $extension_id
     * @return mixed
     */
    public function getExtensionTypeCode($extension_id){
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension` WHERE extension_id = " . (int)$extension_id )->row;
    }

    /**
     * Shows the name of the installation file
     * @param $extension_install_id
     * @return mixed
     */
    public function getNameFileZip($extension_install_id){
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension_install` WHERE extension_install_id = " . (int)$extension_install_id )->row;
    }

    /**
     * Extract all installation files
     * @param $extension_install_id
     * @return mixed
     */
    public function getExtensionPath($extension_install_id){
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension_path` WHERE extension_install_id = " . (int)$extension_install_id )->rows;
    }
}