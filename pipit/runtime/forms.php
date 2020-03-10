<?php
    /**
     * 
     */
    // function pipit_form_response($formID, $check_key_in_post = true, $form_submitted = true) {
    //     $result = [
    //         'status' => 404,
    //         'errors' => [],
    //         'message' => 'The form was not submitted',
    //     ];

        
    //     // check the form was submitted from $_POST
    //     if($check_key_in_post && $formID != pipit_get_formID_from_key( PerchUtil::post('cms-form') )) $form_submitted = false;
        

    //     if($form_submitted) {
    //         $Perch = Perch::fetch();
    //         $result['errors'] = $Perch->get_form_errors($formID);
    
    //         if(empty($result['errors'])) {
    //             $result['status'] = 200;
    //             $result['message'] = 'The form was submitted successfully';
    //         } else {
    //             $result['status'] = 422;
    //             $result['message'] = 'You have some errors';
    //         }
    //     }


    //     PerchUtil::debug($result);
    //     return $result;
    // }



    /**
     * 
     */
    function pipit_form_response($formID, $opts = [], $return = false) {
        $form_submitted = true;
        $response = [
            'status' => 404,
            'errors' => [],
            'message' => 'The form was not submitted',
        ];

        $default_opts = ['dispatch' => true];
        $opts = array_merge($default_opts, $opts);
        

        $content_type = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if (strpos( $content_type, "application/json" ) !== false) {
            $result = pipit_form_handle_json($formID, $opts);
        } else {
            $result = pipit_form_handle_post($formID, $opts);
        }




        if($result) {
            $Perch = Perch::fetch();

            if($formID != pipit_get_formID_from_key( $result['key'] )) {
                // do something
            }


            if($opts['dispatch']) {
                // SubmittedForm relies on $_POST for validation
                $_POST = array_merge($_POST, $result['data']);
    
                // dispatch and let Perch call the relevant {app}_form_handler() functions
                $Perch->dispatch_form($result['key'], $result['data'], $result['files']);
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
        }


        if($return) return $response;
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