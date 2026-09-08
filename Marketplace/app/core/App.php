<?php 
class App{
    protected $controllers = "Home";
    protected $method = "index";
    protected $params = [];

    public function __construct()
    {
        $url = $this->parseURL() ?? [];

        if(isset($url[0]) && file_exists(APP_ROOT . '/controllers/' . ucfirst($url[0]) . '.php')){
            $this->controllers = ucfirst($url[0]);
            unset($url[0]);
        }

        require_once APP_ROOT . '/controllers/' . $this->controllers . '.php';
        $this->controllers = new $this->controllers;

        if(isset($url[1]) && method_exists($this->controllers, $url[1])){
            $this->method = $url[1];
            unset($url[1]);
        }

        $this->params = $url ? array_values($url) : [];
        call_user_func_array([$this->controllers, $this->method], $this->params);
    }

    public function parseURL(){
        $url = $_GET['url'] ?? '';

        if ($url === '') {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '';
            $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath . '/')) {
                $path = substr($path, strlen($basePath));
            }
            $url = trim($path, '/');
        }

        if ($url !== '') {
            $url = rtrim($url, '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            return explode('/', $url);
        }

        return [];
    }
}
?>
