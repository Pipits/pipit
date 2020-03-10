<?php
    /**
     * 
     */
    function pipit_form_response($formID, $check_key_in_post = true, $form_submitted = true) {
        $result = [
            'status' => 404,
            'errors' => [],
            'message' => 'The form was not submitted',
        ];

        
        // check the form was submitted from $_POST
        if($check_key_in_post && $formID != pipit_get_formID_from_key( PerchUtil::post('cms-form') )) $form_submitted = false;
        

        if($form_submitted) {
            $Perch = Perch::fetch();
            $result['errors'] = $Perch->get_form_errors($formID);
    
            if(empty($result['errors'])) {
                $result['status'] = 200;
                $result['message'] = 'The form was submitted successfully';
            } else {
                $result['status'] = 422;
                $result['message'] = 'You have some errors';
            }
        }


        PerchUtil::debug($result);
        return $result;
    }



    

    /**
     * 
     */
    function pipit_api_form_response($apps, $formID, $template_path, $return = false) {
        $content_type = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        if (strpos( $content_type, "application/json" ) !== false ) {
            $content = trim(file_get_contents("php://input"));
            $data = json_decode($content, true);
            // pipit_r($data);

            if($data !== NULL) {
                // template path should have a .html file extension
                // TODO: handle apps local templates (below only works for user templates in PERCH_PATH/templates)
                if ($template_path && substr($template_path, -5)!=='.html') $template_path .= '.html';
                $template_path = "/templates/$template_path";

                // generate form key
                $key = base64_encode("$formID:$apps:$template_path");
                
                // dispatch and let Perch call the relevant {app}_form_handler() functions
                $_POST = array_merge($_POST, $data);
                $Perch = Perch::fetch();
                $Perch->dispatch_form($key, $data, $_FILES);

                // $API = new PerchAPI(1.0, 'pipit');
                // $SubmittedForm = $API->get('SubmittedForm');
                // $_POST = array_merge($_POST, $data);
                // $SubmittedForm->populate($formID, $template_path, $data, null);
            }
        } else {
            PerchUtil::debug('No JSON response received', 'notice');
        }


        $response =  pipit_form_response($formID, false);
        if($return) return $response;
        http_response_code($response['status']);
        echo json_encode($response);
        exit;
    }







    /**
     * 
     */
    function pipit_api_form_response_with_key($formID, $return = false) {
        $content_type = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        $key = $key_formID = '';
        $form_submitted = false;

        if (strpos( $content_type, "application/json" ) !== false ) {
            $content = trim(file_get_contents("php://input"));
            $data = json_decode($content, true);

            if($data !== NULL) {
                if(isset($data['cms-form'])) {
                    $key = $data['cms-form'];
                    $key_formID = pipit_get_formID_from_key($key);
                    unset($data['cms-form']);
                }

                if($formID == $key_formID) {
                    // dispatch and let Perch call the relevant app_form_handler() functions
                    $form_submitted = true;
                    $_POST = array_merge($_POST, $data);
                    $Perch = Perch::fetch();
                    $Perch->dispatch_form($key, $data, $_FILES);
                }
            }
        }


        $response =  pipit_form_response($formID, false, $form_submitted);
        if($return) return $response;
        http_response_code($response['status']);
        echo json_encode($response);
        exit;
    }



    

    /**
     * 
     */
    function pipit_posted_api_form_response($apps, $formID, $template_path, $return = false) {
        $data = $_POST;
        $form_submitted = false;

        
        // dispatch and let Perch call the relevant app_form_handler() functions
        $form_submitted = true;
        

        if ($template_path && substr($template_path, -5)!=='.html') $template_path .= '.html';
        $template_path = "/templates/$template_path";

        // generate form key
        $key = base64_encode("$formID:$apps:$template_path");


        $Perch = Perch::fetch();
        $Perch->dispatch_form($key, $data, $_FILES);
        

        $response =  pipit_form_response($formID, false, $form_submitted);
        if($return) return $response;
        http_response_code($response['status']);
        echo json_encode($response);
        exit;
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
    function pipit_posted_form_response($formID) {
        perch_find_posted_forms();
        return pipit_form_response($formID);
    }


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