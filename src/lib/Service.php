<?php
/**
 * Created by PhpStorm.
 * User: Ning
 */

namespace NzhC\Ithenticate\lib;


use DOMDocument;
use DOMXPath;
use NzhC\Ithenticate\enum\IthenticateEnum;
use NzhC\Ithenticate\exception\IthenticateAuthException;
use NzhC\Ithenticate\exception\IthenticateCurlException;
use NzhC\Ithenticate\exception\IthenticateHttpException;
use NzhC\Ithenticate\exception\IthenticateRuntimeException;
use think\App;
use think\facade\Config;

class Service
{
    private string $requestXml;

    private string $method;

    private array $params;

    private array $config;

    private string $documentContent;

    public function __construct()
    {
        $this->configBuild();
    }

    /**
     * @param string $documentContent
     */
    public function setDocumentContent(string $documentContent): void
    {
        $this->documentContent = $documentContent;
    }

    /**
     * @param string $method
     */
    public function setIthenticateMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @notes configBuild
     * @author n
     */
    private function configBuild():void
    {
        $this->config = require __DIR__.'/../../config/xmlrpc.php';

        if (strpos(App::VERSION, '6.') === 0) {
            $this->config = array_merge($this->config, Config::get('ithenticate') ?? []);
        } else {
            $this->config = array_merge($this->config, Config::get('ithenticate.') ?? []);
        }
    }

    /**
     * @notes buildRequestXml
     * @author n
     */
    private function buildRequestXml():void
    {
        $this->requestXml = xmlrpc_encode_request($this->method, [$this->params]);

        if ($this->method === IthenticateEnum::METHOD_DOCUMENT_ADD)
        {
            $this->requestXml = $this->removeLineBreaks($this->requestXml);
        }
    }

    /**
     * @notes removeLineBreaks
     * @param string $xml
     * @return false|string
     * @author n
     */
    private function removeLineBreaks(string $xml)
    {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($xml);

        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query(IthenticateEnum::UPLOAD_TAG_QUERY);

        if ($nodes->length > 0) {
            $base64Node = $nodes->item(0);

            $base64Node->nodeValue = $this->documentContent ?? '';

            return $doc->saveXML();
        } else {
            throw new IthenticateRuntimeException(IthenticateEnum::FAILED_REPLACING_LINE_BREAK);
        }
    }

    /**
     * @throws IthenticateCurlException
     * @throws IthenticateHttpException
     */
    public function curlXmlRpc()
    {
        $this->buildRequestXml();
        $ch = curl_init($this->config['xml_rpc_url'] ?? '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestXml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml',
            'User-Agent: PHP XML-RPC Client'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout'] ?? '');

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $responseXml = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            throw new IthenticateCurlException("cURL Error: " . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != IthenticateEnum::SUCCESSFUL_RESPONSE) {
            throw new IthenticateHttpException("HTTP Error: " . $httpCode,$httpCode);
        }

        $responseData = xmlrpc_decode($responseXml);

        if (is_array($responseData) && xmlrpc_is_fault($responseData)) {
            throw new IthenticateHttpException("XML-RPC Fault: Code={$responseData['faultCode']}, String={$responseData['faultString']}");
        } else {

            if (($responseData['status'] ?? null) !== IthenticateEnum::SUCCESSFUL_RESPONSE)
            {
                if (($responseData['status'] ?? null) === IthenticateEnum::UNAUTHENTICATED)
                {
                    throw new IthenticateAuthException(json_encode($responseData['messages']) ?? IthenticateEnum::FAILED_AUTH ,$responseData['status'] ?? IthenticateEnum::REQUEST_ERROR);
                }else{
                    throw new IthenticateRuntimeException(json_encode($responseData) ?? IthenticateEnum::SYSTEM_ERROR
                        ,$responseData['status'] ?? IthenticateEnum::REQUEST_ERROR,
                        json_encode($responseData));
                }
            }

            return $responseData;
        }
    }

    /**
     * @notes getDataId
     * @param array $data
     * @param string $dataFiled
     * @return int|null
     * @author n
     * @date 2025/8/14
     */
    public function getDataId(array $data,string $dataFiled):?int
    {
        $dataID = null;

        if (is_array($data) && !empty($data))
        {
            foreach ($data as $item)
            {
                if (($item['name'] ?? null) === $dataFiled)
                {
                    $dataID = $item['id'] ?? null;
                }
            }
        }

        return $dataID;
    }

    /**
     * @notes getDocumentContent
     * @param string $filePath
     * @return string
     * @author n
     * @date 2025/8/14
     */
    public function getDocumentContent(string $filePath):string
    {
        $fileValidate = new FileValidate($filePath);

        $fileValidate->allowedTypes();

        $fileValidate->maxSize();

        return file_get_contents($filePath);
    }

}