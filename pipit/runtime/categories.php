<?php
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