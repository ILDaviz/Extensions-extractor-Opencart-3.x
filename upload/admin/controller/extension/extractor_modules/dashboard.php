<?php

class ControllerExtensionExtractorModulesDashboard extends Controller
{

    private $location_repository = DIR_STORAGE . 'mod_repository/';
    private $name_file_cache = 'cache_modules_repository';

    public function __construct($registry)
    {
        parent::__construct($registry);
    }

    /**
     * This method prints the name to be displayed in the menu sidebar
     * @return string
     */
    public function name_menu()
    {
        $this->load->language('extension/extractor_modules/dashboard');
        return $this->language->get('menu_title');
    }

    public function index()
    {
        $this->load->language('extension/extractor_modules/dashboard');
        $this->load->model('extension/extractor_modules/dashboard');

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/extractor_modules/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['location_folder'] = $this->location_repository;

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/extractor_modules/dashboard_form', $data));
    }

    /**
     * ==================
     * This is API loop.
     * ==================
     */
    public function api_create_repository()
    {

        $json = array();

        $this->load->model('extension/extractor_modules/dashboard');
        $this->load->language('extension/extractor_modules/dashboard');

        // Check old position
        if (isset($this->request->get['position_old'])) {
            $position_old = (int)$this->request->get['position_old'];
        } else {
            $position_old = 0;
        }

        $json['position_old'] = $position_old;

        if ($position_old == 0) {
            // Save on cache list modules
            $this->cache->set($this->name_file_cache, $this->model_extension_extractor_modules_dashboard->getModification());
            $total = count($this->cache->get($this->name_file_cache));

            $json['position_new'] = 1; // New position
            $json['text_result'] = '0 --- ' . $this->language->get('extract_list_extension'); // Print result
            $json['live_n'] = 1; // N° position
            $json['total_element'] = $total; // Total element
            $json['total'] = round((1 / $total - 1) * 100, 2); // % for progress bar
            // Next process
            $json['next'] = str_replace('&amp;', '&', $this->url->link('extension/extractor_modules/dashboard/api_create_repository', '&user_token=' . $this->session->data['user_token'] . '&position_old=1', true));

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));

        } else {

            $total = count($this->cache->get($this->name_file_cache));

            $info_upload = $this->elaboration($position_old); // Process elaboration

            $position_new = (int)$info_upload['position']; // New position
            $json['position_new'] = $info_upload['position'];
            $json['text_result'] = '' . $position_old . ' --- ' . $info_upload['text_result']; // Print result
            $json['live_n'] = $position_new; // N° position
            $json['total_element'] = $total; // Total element
            $json['total'] = round(($position_new / $total) * 100, 2); // % for progress bar

            // Check if position status loop
            if ($position_new <= $total) {
                // Next process
                $json['next'] = str_replace('&amp;', '&', $this->url->link('extension/extractor_modules/dashboard/api_create_repository', '&user_token=' . $this->session->data['user_token'] . '&position_old=' . $position_new, true));
            } else {
                // Completed
                $json['success'] = $this->language->get('process_complete');
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }

    /**
     * This method elaborate request end create extension files and folders.
     * @param $n
     * @return array
     */
    private function elaboration($n)
    {

        $this->load->model('extension/extractor_modules/dashboard');

        $data = [];

        $mods = $this->cache->get($this->name_file_cache);
        $text_result = 'Extension: ' . $this->cleanString($mods[$n]['name']) . ' ';

        // File name
        $name_file_installation = $this->model_extension_extractor_modules_dashboard->getNameFileZip($mods[$n]['extension_install_id']);

        // Create folder
        $new_location = $this->createNewLocation($this->cleanString($mods[$n]['name']));

        // Add file install.xml
        try {
            $this->saveFile($mods[$n]['xml'], 'install.xml', $new_location);
            $text_result .= '|Add File install.xml ';
        } catch (Exception $e) {
            $this->log->write('Error extractor module: ' . $e->getMessage());
        }

        // Add file Note_mod.txt
        try {
            $this->saveFile(print_r($mods[$n], true), 'note_mod.txt', $new_location);
            $text_result .= '|Add note_mod.txt ';
        } catch (Exception $e) {
            $this->log->write('Error extractor module: ' . $e->getMessage());
        }

        // Create Folder end add file
        $mod_files = $this->model_extension_extractor_modules_dashboard->getExtensionPath($mods[$n]['extension_install_id']);
        foreach ($mod_files as $mod_file) {
            $path_file = $mod_file['path'];
            try {
                $this->saveFile('', '', $new_location, $path_file);
                $text_result .= '|Add Folder Upload and Files ';
            } catch (Exception $e) {
                $this->log->write('Error extractor module: ' . $e->getMessage());
            }
        }

        // Create file .ocmod.zip
        if (isset($name_file_installation['filename'])) {
            try {
                $this->createZipFromDir($new_location, $new_location . "/" . $name_file_installation['filename']);
                $text_result .= '|Create ocmod.zip';
            } catch (Exception $e) {
                $this->log->write('Error extractor module: ' . $e->getMessage());
            }
        } else {
            try {
                $this->createZipFromDir($new_location, $new_location . "/" . $this->cleanString($mods[$n]['name']));
                $text_result .= '|Create ocmod.zip';
            } catch (Exception $e) {
                $this->log->write('Error extractor module: ' . $e->getMessage());
            }
        }

        $n++;

        $data['text_result'] = $text_result;
        $data['position'] = $n;

        return $data;

    }

    /**
     * Clear string
     * @param $string
     * @return string|string[]|null
     */
    private function cleanString($string)
    {
        $string = strtolower($string); // Lover case string
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
        return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
    }

    /**
     * I create the zip file of the whole folder
     * @param $dir
     * @param $zip_file
     * @return false|ZipArchive
     */
    private function createZipFromDir($dir, $zip_file)
    {
        $zip = new ZipArchive;
        if (true !== $zip->open($zip_file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
            return false;
        }
        $this->zipDir($dir, $zip);
        return $zip;
    }

    /**
     * Add any folders and sub files inside the zip
     * @param $dir
     * @param $zip
     * @param string $relative_path
     */
    private function zipDir($dir, $zip, $relative_path = DIRECTORY_SEPARATOR)
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                if (is_file($dir . $file)) {
                    $zip->addFile($dir . $file, $file);
                } elseif (is_dir($dir . $file)) {
                    $this->zipDir($dir . $file, $zip, $relative_path . $file);
                }
            }
        }
        closedir($handle);
    }

    /**
     * I create a new position
     * @param $location_name
     * @return string
     */
    private function createNewLocation($location_name)
    {
        $new_location = $this->location_repository . $location_name;
        if (!is_dir($new_location)) {
            mkdir($new_location);
        }
        return $new_location;
    }

    /**
     * Save file
     * @param $content
     * @param $file_name
     * @param $file_location_repo
     * @param null $file_location_online
     */
    private function saveFile($content, $file_name, $file_location_repo, $file_location_online = null)
    {

        // Check if the repo folder is present
        if (!isset($file_location_repo)) new Exception('Error not file location repository');

        $path_local_file = null;
        if (isset($file_location_online)) {
            if (substr($file_location_online, 0, 5) == 'admin') {
                $path_local_file = DIR_APPLICATION . substr($file_location_online, 6);
            }
            if (substr($file_location_online, 0, 7) == 'catalog') {
                $path_local_file = DIR_CATALOG . substr($file_location_online, 8);
            }
            if (substr($file_location_online, 0, 5) == 'image') {
                $path_local_file = DIR_IMAGE . substr($file_location_online, 6);
            }
            if (substr($file_location_online, 0, 6) == 'system') {
                $path_local_file = DIR_SYSTEM . substr($file_location_online, 7);
            }

            if (!is_dir($path_local_file)) { // Check if is not folder
                // I break the path in an array
                $element_path = explode(DIRECTORY_SEPARATOR, $file_location_online);
                // Delete last element es: element.php
                unset($element_path[count($element_path) - 1]);

                // Make folder upload
                if (!is_dir($file_location_repo . "/upload/")) {
                    mkdir($file_location_repo . "/upload/");
                }
                // Create sub folder
                $path_repo_new = $file_location_repo . "/upload";
                foreach ($element_path as $n) {
                    $path_repo_new .= '/';
                    $path_repo_new .= $n;
                    if (!is_dir($path_repo_new)) {
                        mkdir($path_repo_new);
                    }
                }
                // I copy the files into the new destination
                copy($path_local_file, $file_location_repo . "/upload/" . $file_location_online);
            }
        } else {
            // Add single element
            $file = fopen($file_location_repo . "/" . $file_name, "w");
            fwrite($file, $content);
            fclose($file);
        }
    }
}
