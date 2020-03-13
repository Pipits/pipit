<?php
    spl_autoload_register(function($class_name){
		if (strpos($class_name, 'Pipit_')===0) {
			include(PERCH_PATH.'/addons/apps/pipit/lib/'.$class_name.'.class.php');
			return true;
		}
		return false;
	});



    /**
     * 
     */
    function pipit_form_response($formID, $opts = [], $return = false) {
        $response = [
            'status' => 404,
            'errors' => [],
            'message' => 'The form was not submitted',
        ];
        
        
        $key = false;
        $default_opts = ['dispatch' => false];
        $opts = array_merge($default_opts, $opts);
        $content_type = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';


        if (strpos( $content_type, "application/json" ) !== false) {
            $content = trim(file_get_contents("php://input"));
            $data = json_decode($content, true);
        } else {
            $data = $_POST;
        }


        if($data == NULL) return false;



        if( isset($opts['template'], $opts['app']) ) {
            $template_path = $opts['template'];
            $app = $opts['app'];

            // template
            if( ! Pipit_Util::template_exists($template_path) ) {
                return false;
            }

            // generate form key
            $template_path = Pipit_Util::format_template_path($template_path);
            $key = base64_encode("$formID:$app:$template_path");
            

        } elseif(isset($data['cms-form'])) {
            $key = $data['cms-form'];
            unset($data['cms-form'], $_POST['cms-form']);

            $key_formID = pipit_get_formID_from_key($key);
            // if($formID != $key_formID) return false;
            
        }


        
        if(!$key) return false;


        $Perch = Perch::fetch();

        if($formID != pipit_get_formID_from_key( $key )) {
            // do something
        }


        if($opts['dispatch']) {
            // SubmittedForm relies on $_POST for validation
            // dispatch and let Perch call the relevant {app}_form_handler() functions
            $_POST = array_merge($_POST, $data);
            $Perch->dispatch_form($key, $data, $_FILES);
        }


        // get form errors logged by SubmittedForm
        $response['errors'] = $Perch->get_form_errors($formID);

        if(empty($response['errors'])) {
            $response['status'] = 200;
            $response['message'] = 'The form was submitted successfully';
        } else {
            $response['status'] = 422;
            $response['message'] = 'You have some errors';
        }
        


        if($return) return $response;
        header('Content-Type: application/json');
        http_response_code($response['status']);
        echo json_encode($response);
        exit;
    }



    


    /**
     * 
     */
    function pipit_form_handle_json($formID, $opts = []) {
        $content = trim(file_get_contents("php://input"));
        $data = json_decode($content, true);
        if($data == NULL) return false;


        if( isset($opts['template'], $opts['app']) ) {
            $template_path = $opts['template'];
            $app = $opts['app'];

            // template
            if ($template_path && substr($template_path, -5)!=='.html') $template_path .= '.html';
            $template_path = "/templates/$template_path";
            $template_file  = PerchUtil::file_path(PERCH_TEMPLATE_PATH.'/'.$template_path);
            if(!is_file($template_file)) return false;

            // generate form key
            $key = base64_encode("$formID:$app:$template_path");
            
            return [
                'key' => $key,
                'data' => $data,
                'files' => $_FILES
            ];
        }



        if(isset($data['cms-form'])) {
            $key = $data['cms-form'];

            $key_formID = pipit_get_formID_from_key($key);
            // if($formID != $key_formID) return false;            
            unset($data['cms-form']);
            
            return [
                'key' => $key,
                'data' => $data,
                'files' => $_FILES
            ];
        }


        return false;
    }






    /**
     * 
     */
    function pipit_form_handle_post($formID, $opts =[]) {
        $data = $_POST;
        $form_submitted = false;

        
        if(isset($opts['template'], $opts['app'])) {
            $template_path = $opts['template'];
            $app = $opts['app'];

            // template
            if ($template_path && substr($template_path, -5)!=='.html') $template_path .= '.html';
            $template_path = "/templates/$template_path";
            $template_file  = PerchUtil::file_path(PERCH_TEMPLATE_PATH.'/'.$template_path);
            if(!is_file($template_file)) return false;

            // generate form key
            $key = base64_encode("$formID:$app:$template_path");

            return [
                'key' => $key,
                'data' => $data,
                'files' => $_FILES
            ];
        }
        

        if(isset($data['cms-form'])) {
            $key = $data['cms-form'];

            $key_formID = pipit_get_formID_from_key($key);
            unset($data['cms-form']);

            if($formID != $key_formID) return false;

            return [
                'key' => $key,
                'data' => $data,
                'files' => $_FILES
            ];
        }


        
        
        return false;
    }





    /**
     * 
     */
    function pipit_get_formID_from_key($key) {
        $key = base64_decode($key);
        $parts = explode(':', $key);
        return isset($parts[0]) ? $parts[0] : '';
    }


    /**
     * 
     */
    function pipit_get_form_key_parts($key) {
        $key = base64_decode($key);
        $parts = explode(':', $key);
        return $parts;
    }


    /**
     * 
     */
    // function pipit_posted_form_response($formID) {
    //     perch_find_posted_forms();
    //     return pipit_form_response($formID);
    // }


    /**
     * 
     */
    function pipit_set_form_error_vars() {
        $Perch = Perch::fetch();
        $vars = [];

        foreach($Perch->form_errors as $formID => $errors) {
            foreach($errors as $field => $error_type) {
                $vars[$formID . '_error_' . $field] = $error_type;
            }
        }

        if($vars) PerchSystem::set_vars($vars);
    }