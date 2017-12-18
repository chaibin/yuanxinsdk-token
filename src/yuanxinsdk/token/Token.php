<?php
namespace yuanxinsdk\token;


/**
 * token获取和校验类  可自行改写
 *
 * @author  cdb
 * @package api\components\token
 */
class Token
{
    protected $onlineHost = 'http://gateway.miaoshou.com';
    protected $testHost = 'http://test.gateway.miaoshou.com';
    protected $devHost = 'http://cdb.sdk.com';  //请绑定host 到 250
    protected $uri = '/v1/token';

    public $requester;          //请求白名单
    public $resource;           // 资源
    public $env = 'dev';        //环境标识

    protected $url;             //curl 请求地址
    protected $params;         //post 请求参数
    public $result = [];       //返回结果

    public static $_ins = null;//存储实例

    /**
     * 初始化
     *
     * @param array $config
     */
    protected function __construct(array $config)
    {
        if(!isset($config['requester']) || !isset($config['resource'])){
            throw new Exception('requester and resource is required');
        }
        foreach ($config as $key=>$value){
            if(!$value) continue;
            $this->$key = $value;
        }
        $this->setUrl();
    }

    /**
     * 获取实例
     *
     * @param array $config 配置数组
     * @return null|static
     * @throws Exception
     */
    public static function getIns(array $config)
    {
        if(!isset($config['requester']) || !isset($config['resource'])){
            throw new Exception('requester and resource is must be set');
        }
        return static::$_ins instanceof self ?
                static::$_ins :
                new static($config) ;
    }

    /**
     * 获取token
     *
     * @return array|bool|mixed
     */
    public function getToken()
    {
        $this->url .= '?requester=' . $this->requester . '&resource=' . $this->resource;
        $this->result = $this->curl();
        return  $this->result;
    }

    /**
     * 验证token
     *
     * @param $token
     * @return $this
     */
    public function validToken($token)
    {
        $params = [
            'requester' => $this->requester,
            'resource' => $this->resource,
            'token' => addslashes(trim($token))
        ];
        $this->setParams($params);
        $this->result = json_decode($this->curl(), true);
        return (bool)$this->result['data'];
    }

    /**
     * 获取post 参数
     *
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * 设置post 参数
     *
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * 获取请求url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * 设置curl 请求地址
     */
    public function setUrl()
    {
        $name = $this->env . 'Host';
        $this->url = $this->$name . $this->uri;
    }

    /**
     * curl $params为空则get方式调用,$params不空为POST调用
     * @param string $url
     * @return boolean|mixed
     */
    public function curl($header = [], $ip4 = false)
    {
        $url = $this->getUrl();
        $params = $this->getParams();
        if (empty($url)) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!empty($params)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($ip4) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $result = curl_error($ch);
        }
        curl_close($ch);
        //var_dump($url, $params);
        return $result;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . $name)) {
            throw new Exception('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new Exception('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new Exception('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

}
