<?php

    /**
     * Outputs a formatted print_r()
     */
    function pipit_r($var) {
        echo '<pre>' . print_r($var, 1) . '</pre>';
    }




    
    /**
     * Get an element from the $_GET array
     * 
     * @param string $str           element key $_GET[$str]
     * @param bool $return_array    returns the value as an array
     * @param mixed $default        default value to be returned if $_GET[$str] does not exist
     * 
     * @return string|array
     */
    function pipit_get($str, $return_array = false, $default = false) {
        
        if(isset($_GET[$str])) {
            if(is_array($_GET[$str])) {
                
                $result = array();
                foreach($_GET[$str] as $item) {
                    $result[] = rawurldecode($item);
                }
                return $result;

            } elseif($_GET[$str] != '') {

                $result = rawurldecode($_GET[$str]);
                if($return_array) {
                    return [$result];
                } else {
                    return $result;
                }
                 
            }
        }

        return $default;
    }





    /**
     * Check if user logged into Perch control panel
     * This is not part of Perch's public API
     */
    function pipit_perch_user_logged_in(){
        $Users = new PerchUsers;
        $CurrentUser = $Users->get_current_user();
        
        if (is_object($CurrentUser) && $CurrentUser->logged_in()) {
            return true;
        }

        return false;
    }





    /**
     * Cache bust CSS and JS
     * 
     * @param string $path   Path to an asset file
     * @param string $type   Type of version
     * 
     */
    function pipit_version($path, $type = 'param') {
        $full_path = dirname(PERCH_PATH) . $path;
        if (file_exists($full_path)) {
            $path_info = pathinfo($path);
            $filename = substr($path, 0, strrpos($path, '.'));
            

            switch($type) {
                case 'param':
                    echo $path . '?v=' . filemtime($full_path);
                    break;

                case 'name':
                    echo $filename . '.' . filemtime($full_path) . '.' . $path_info['extension'];
                    break;
            }
        }
    }





    /**
     * Get language string from URL with the pattern /{lang}/my-page
     * 
     * @param array $accepted_langs     array of accepted languages
     * @param string $default_lang      default language string
     * 
     * @return string
     */
    function pipit_get_lang($accepted_langs, $default_lang =''){
        $url = perch_page_url(['include-domain' => false], true);
        $url_parts = explode('/', $url);

        if(isset($url_parts[1]) && in_array($url_parts[1], $accepted_langs)) {
            return $url_parts[1];
        } else {
            return $default_lang;
        }
    }





    /**
     * Renders a Perch template
     * 
     * @param string $template  Template path
     * @param array $data       Array containing the data to be rendered by the template
     * @param array $opts       Options array
     * @param bool $return      Set to true to have the rendered template returned instead of echoed
     * 
     */
    function pipit_template($template, $data, $opts = array(), $return = false) {
        $API  = new PerchAPI(1.0, 'pipit');
        $Template = $API->get('Template');

        $default_opts = [
            'namespace' => 'content',
        ];

        $opts = array_merge($default_opts, $opts);
        $Template->set($template, $opts['namespace']);


        // associate array rendered as a single item ['name' => 'John Silver'],
        // otherwise render as a group [['name' => 'John Silver'], ['name' => 'Jason Bourne']]
        if (!PerchUtil::is_assoc($data)) {

            if(isset($opts['paginate']) && isset($opts['count'])) {
                $Paging = $API->get('Paging');
                $Paging->set_per_page($opts['count']);
                $Paging->set_total(PerchUtil::count($data));
                
                if(isset($opts['pagination-var'])) {
                    $Paging->set_qs_param($opts['pagination-var']);
                }
            
                $paging_array = $Paging->to_array($opts);
                
                // add paging vars to each item in $data
                if (PerchUtil::count($data)) {
                    array_walk($data, function(&$item) use($paging_array) {
                        foreach($paging_array as $key=>$val) {
                            $item[$key] = $val;
                        }
                    });
                }

                // get the ($data) array elements for a given page
                $offset = $Paging->per_page() * ($Paging->current_page()-1);
                $data = array_slice($data, $offset, $Paging->per_page());

            } elseif(isset($opts['count'])) {
                // count limit with no pagination
                $data = array_slice($data, 0, $opts['count']);
            }

            $html = $Template->render_group($data);
        }else{
            $html = $Template->render($data);
        }
        

        // layout includes, forms, etc
        $html = $Template->apply_runtime_post_processing($html);
        

        if($return) return $html;
        echo $html;
        PerchUtil::flush_output();
    }




    
    /**
     * Outputs a category set
     * 
     * @param string $slug      Category Set slug
     * @param array $opts       Options array
     * @param bool $return      Set to true to have the rendered template returned instead of echoed
     * 
     */
    function pipit_category_set($slug, $opts = array(), $return = false) {
        $API  = new PerchAPI(1.0, 'pipit');
        $Sets = new PerchCategories_Sets($API);

        $Set = $Sets->get_one_by('setSlug', $slug);

        if(!$Set) {
            PerchUtil::debug("Category Set with the slug '$slug' could not be found", 'notice');
            return false;
        }
        

        $default_opts = ['template' => 'categories/set.html'];
        $opts = array_merge($default_opts, $opts);
        $set_array = $Set->to_array();
        $render = true;


        if(isset($opts['skip-template']) && $opts['skip-template'] == true) {
            $render = false;

            if(isset($opts['return-html']) && $opts['return-html'] == true) {
                $render = true;
            }
        }


        if($render) {
            $html = pipit_template($opts['template'], $set_array, ['namespace' => 'categories'], true);

            if(isset($opts['skip-template']) && $opts['skip-template'] == true) {
                $result = $set_array;
                $result['html'] = $html;
                return $result;
            }

            if($return) return $html;
            echo $html;
            

        } else {
            return $set_array;
        }
    }






    /**
     * Get category path from ID
     * 
     * @param string $source    Category ID
     * 
     * @return string|boolean
     */
    function pipit_category_get_path($source){
        // do we have an ID?
        if(is_numeric($source)) {
            $cat = perch_categories([
                'filter' => 'catID',
                'value' => $source,
                'skip-template' => true,
            ]);
          
            if($cat) return $cat[0]['catPath'];
          
  
        } else {
            // we have a string - it's catPath
            return $source;
        }


        return false;
    }





    
    /**
     * Get category ID from path
     * 
     * @param string $source    Category ID
     * 
     * @return string|boolean
     */
    function pipit_category_get_id($source){
        // do we have an ID?
        if(!is_numeric($source)) {
            $cat = perch_categories([
                'filter' => 'catPath',
                'value' => $source,
                'skip-template' => true,
            ]);
          
            if($cat) return $cat[0]['catID'];
          
  
        } else {
            // we have a numerical value - it's catID
            return $source;
        }


        return false;
    }