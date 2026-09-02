<?php

class Iugu_APIRequest
{
    /**
     * Status HTTP da última resposta recebida por esta instância, JSON ou não. Nulo antes da
     * primeira requisição e quando o cURL não obteve resposta (falha de rede, timeout).
     *
     * @var int|null
     */
    public $lastResponseCode = null;

    /**
     * Cabeçalhos da última resposta recebida por esta instância, com o nome em minúsculas.
     * Cabeçalho repetido vira uma lista de valores.
     *
     * @var array
     */
    public $lastResponseHeaders = array();

    /**
     * Chave de API desta instância. Nula usa a chave global de Iugu::setApiKey().
     *
     * @var string|null
     */
    private $apiKey = null;

    /**
     * @param  string|null  $apiKey  chave de API desta instância; nula usa a global
     */
    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Chave de API usada nas requisições desta instância: a própria ou, na falta dela, a global.
     *
     * @return string|null
     */
    public function getApiKey()
    {
        return $this->apiKey !== null ? $this->apiKey : Iugu::getApiKey();
    }

    private function _defaultHeaders($headers = [])
    {
        $headers[] = 'Authorization: Basic ' . base64_encode($this->getApiKey() . ':');
        $headers[] = 'Accept: application/json';
        $headers[] = 'Accept-Charset: utf-8';
        $headers[] = 'User-Agent: Iugu PHPLibrary';
        $headers[] = 'Accept-Language: pt-br;q=0.9,pt-BR';

        return $headers;
    }

    /**
     * Executa uma requisição à API e devolve o corpo decodificado.
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $data
     * @param  array  $headers  cabeçalhos extras desta requisição, no formato 'Nome: valor'
     *                          (por exemplo 'Idempotency-Key: ...'), acrescentados aos padrão
     * @return mixed
     * @throws IuguAuthenticationException  chave de API não configurada
     * @throws IuguRequestException  resposta que não é JSON; o status HTTP vai em getCode()
     * @throws IuguObjectNotFound  resposta 404
     */
    public function request($method, $url, $data = [], $headers = [])
    {
        global $iugu_last_api_response_code;

        $this->lastResponseCode = null;
        $this->lastResponseHeaders = array();

        if ($this->getApiKey() == null) {
            Iugu_Utilities::authFromEnv();
        }

        if ($this->getApiKey() == null) {
            throw new IuguAuthenticationException('Chave de API não configurada. Utilize Iugu::setApiKey(...) para configurar.');
        }

        $headers = array_merge($this->_defaultHeaders(), array_values((array) $headers));

        list($response_body, $response_code, $response_headers) = $this->requestWithCURL($method, $url, $headers, $data);

        $this->lastResponseCode = $response_code > 0 ? (int) $response_code : null;
        $this->lastResponseHeaders = $response_headers;

        if (Iugu::getLogErrors()) {
            error_log('IUGU - Requisição executada, Response Code: ' . $response_code . ', Response: ' . $response_body);
        }

        $response = json_decode($response_body);
        $jsonError = json_last_error();
        if (is_null($response) || $jsonError != JSON_ERROR_NONE) {
            switch ($jsonError) {
                case JSON_ERROR_NONE:
                    $error = 'Nenhum erro identificado';
                    break;
                case JSON_ERROR_DEPTH:
                    $error = 'Máxima profundidade de nós atingida';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $error = 'JSON inválido ou mal formado';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $error = 'Caractere de controle encontrado';
                    break;
                case JSON_ERROR_SYNTAX:
                    $error = 'JSON malformado';
                    break;
                case JSON_ERROR_UTF8:
                    $error = 'Carateres UTF-8 malformados';
                    break;
                default:
                    $error = 'Erro desconhecido (' . $jsonError . ')';
                    break;
            }

            if (Iugu::getLogErrors()) {
                error_log('IUGU - Erro de parse do JSON: ' . $error . ', Response code: ' . $response_code . ', Mensagem de erro: ' . json_last_error_msg() . ', Response: ' . $response_body);
            }

            throw new IuguRequestException($response_body, $response_code);
        }

        if ($response_code == 404) {
            throw new IuguObjectNotFound($response_body, $response_code);
        }

        if (isset($response->errors)) {
            if ((gettype($response->errors) == 'object') && count(get_object_vars($response->errors)) == 0) {
                unset($response->errors);
            } elseif ((gettype($response->errors) == 'object') && count(get_object_vars($response->errors)) > 0) {
                $response->errors = (array) $response->errors;
            }

            if (isset($response->errors) && (gettype($response->errors) == 'string')) {
                $response->errors = $response->errors;
            }
        }

        $iugu_last_api_response_code = $response_code;

        return $response;
    }

    private function encodeParameters($method, $url, $data = [])
    {
        $method = strtolower($method);

        switch ($method) {
            case 'get':
            case 'delete':
                $paramsInURL = Iugu_Utilities::arrayToParams($data);
                $data = null;
                $url = (strpos($url, '?')) ? $url . '&' . $paramsInURL : $url . '?' . $paramsInURL;
                break;
            case 'post':
            case 'put':
                $data = Iugu_Utilities::arrayToParams($data);
                break;
        }

        return [$url, $data];
    }

    private function requestWithCURL($method, $url, $headers, $data = [])
    {
        $curl = curl_init();

        $opts = [];

        list($url, $data) = $this->encodeParameters($method, $url, $data);

        if (strtolower($method) == 'post') {
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $data;
        }
        if (strtolower($method) == 'delete') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        if (strtolower($method) == 'put') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $opts[CURLOPT_POSTFIELDS] = $data;
        }

        $response_headers = array();

        $opts[CURLOPT_URL] = $url;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_CONNECTTIMEOUT] = 30;
        $opts[CURLOPT_TIMEOUT] = 80;
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_HTTPHEADER] = $headers;
        $opts[CURLOPT_HEADERFUNCTION] = function ($curl, $line) use (&$response_headers) {
            $length = strlen($line);
            $parts = explode(':', $line, 2);
            if (count($parts) < 2) {
                return $length;
            }

            $name = strtolower(trim($parts[0]));
            $value = trim($parts[1]);
            if (isset($response_headers[$name])) {
                $response_headers[$name] = array_merge((array) $response_headers[$name], array($value));
            } else {
                $response_headers[$name] = $value;
            }

            return $length;
        };

        $opts[CURLOPT_SSL_VERIFYHOST] = 2;
        $opts[CURLOPT_SSL_VERIFYPEER] = true;
        $opts[CURLOPT_CAINFO] = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data') . DIRECTORY_SEPARATOR . 'ca-bundle.crt';

        curl_setopt_array($curl, $opts);

        $response_body = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return [$response_body, $response_code, $response_headers];
    }
}
