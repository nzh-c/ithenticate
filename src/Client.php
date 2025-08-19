<?php
/**
 * Created by PhpStorm.
 * User: Ning
 * CreateTime: 2021/8/12 下午2:48
 */

namespace NzhC\Ithenticate;

use NzhC\Ithenticate\enum\IthenticateEnum;
use NzhC\Ithenticate\exception\IthenticateAuthException;
use NzhC\Ithenticate\exception\IthenticateHttpException;
use NzhC\Ithenticate\lib\Service;
use think\facade\Cache;

class Client
{
    private ?string $username;

    private ?string $password;

    private string $sid;

    private static ?Client $instance = null;

    private Service $service;

    private string $sidKey;

    private array $serviceConfig;

    /**
     * @return string
     */
    private function getSid(): string
    {
        return $this->sid;
    }

    /**
     * Client constructor.
     * @param string|null $username
     * @param string|null $password
     * @throws exception\IthenticateCurlException
     * @throws exception\IthenticateHttpException
     * @throws exception\IthenticateRuntimeException
     */
    private function __construct(?string $username = null,?string $password = null){
        $this->service = new Service();
        $this->username = $username;
        $this->password = $password;
        $this->serviceConfig = $this->service->getConfig();
        $this->sidKey = 'ithenticate_sid_'.($this->username ?? $this->serviceConfig['d_username']);

        $this->login();
    }

    private function __clone(){}

    /**
     * @notes getInstance
     * @param string|null $username
     * @param string|null $password
     * @return Client|null
     * @throws exception\IthenticateCurlException
     * @throws exception\IthenticateHttpException
     * @throws exception\IthenticateRuntimeException
     * @author n
     */
    public static function getInstance(?string $username = null, ?string $password = null):Client
    {
        if (is_null(self::$instance))
        {
            self::$instance = new self($username,$password);
        }

        return self::$instance;
    }

    /**
     * @notes getReportByDocumentID
     * @param int $documentID
     * @return array|mixed
     * @throws IthenticateHttpException
     * @throws exception\IthenticateCurlException
     * @author n
     */
    public function getReportByDocumentID(int $documentID):array
    {
        return $this->executeWithRetry(function () use($documentID){
            $this->service->setIthenticateMethod(IthenticateEnum::METHOD_DOCUMENT_GET);

            $params = [
                'sid' => $this->getSid(),
                'id' => $documentID
            ];

            $this->service->setParams($params);
            $result = $this->service->curlXmlRpc();

            return $result['documents'] ?? [];
        });
    }


    /**
     * @notes submitDocument
     * @param string $documentContentPath
     * @param int $folderNumber
     * @param string $filename
     * @param string $title
     * @param string $authorFirstname
     * @param string $authorLastname
     * @return array|mixed
     * @throws IthenticateHttpException
     * @throws exception\IthenticateCurlException
     * @author n
     */
    public function submitDocument(
                                    string $documentContentPath,
                                    int $folderNumber,
                                    string $filename = '',
                                    string $title = '',
                                    string $authorFirstname = '',
                                    string $authorLastname = ''):array
    {

        return $this->executeWithRetry(function () use($documentContentPath, $folderNumber,$filename, $title, $authorFirstname, $authorLastname){
            $this->service->setIthenticateMethod(IthenticateEnum::METHOD_DOCUMENT_ADD);

            $documentContent = $this->service->getDocumentContent($documentContentPath);

            $this->service->setDocumentContent(base64_encode($documentContent));

            $placeholderContent = '';

            xmlrpc_set_type($placeholderContent,'base64');

            $uploadItem = [
                'title' => $title,
                'filename' => $filename,
                'author_first' => $authorFirstname,
                'author_last' => $authorLastname,
                'upload' => $placeholderContent,
                'callback_url' => $this->serviceConfig['callback_url'] ?? '',
            ];
            $params = [
                'sid' => $this->getSid(),
                'submit_to' => $this->serviceConfig['submit_to'] ?? 1,
                'folder' => $folderNumber,
                'uploads' => [$uploadItem]
            ];

            $this->service->setParams($params);
            $result = $this->service->curlXmlRpc();
            $documentData = $result['uploaded'] ?? [];

            return $documentData[0] ?? [];
        });
    }

    /**
     * @notes login
     * @throws exception\IthenticateCurlException
     * @throws exception\IthenticateHttpException
     * @throws exception\IthenticateRuntimeException
     * @author n
     */
    private function login():void
    {
        if (Cache::has($this->sidKey))
        {
            $this->sid = Cache::get($this->sidKey);
        }else{
            $this->service->setIthenticateMethod(IthenticateEnum::METHOD_LOGIN);
            $params = [
                'username' => $this->username ?? $this->serviceConfig['d_username'],
                'password' => $this->password ?? $this->serviceConfig['d_password'],
            ];
            $this->service->setParams($params);

            try {
                $result = $this->service->curlXmlRpc();
                $this->sid = $result["sid"] ?? "";
            }catch (\Exception $e)
            {
                throw $e;
            }


            if(empty($this->sid))
            {
                throw new IthenticateHttpException(IthenticateEnum::FAILED_AUTH);
            }

            Cache::set($this->sidKey,$this->sid);
        }

    }

    /**
     * @notes refreshToken
     * @throws IthenticateHttpException
     * @throws exception\IthenticateCurlException
     * @author n
     * @date 2025/8/13
     */
    private function refreshToken()
    {
        Cache::delete($this->sidKey);
        $this->login();
    }

    /**
     * @notes folderAdd
     * @param string $folderName
     * @param string $description
     * @return mixed|null
     * @throws IthenticateHttpException
     * @throws exception\IthenticateCurlException
     * @author n
     */
    public function folderAdd(string $folderName,string $description = ''):?int
    {
        return $this->executeWithRetry(function () use($folderName,$description){
            $groupId = $this->folderGroupCreate();
            $folderId = $this->getFolderByName($folderName);
            if (empty($folderId))
            {
                $this->service->setIthenticateMethod(IthenticateEnum::METHOD_FOLDER_ADD);
                $params = [
                    'sid' => $this->getSid(),
                    'name' => $folderName,
                    'description' => $description,
                    'folder_group' => $groupId,
                    'exclude_quotes' => true,
                    'exclude_biblo' => true,
                ];

                $this->service->setParams($params);

                $result = $this->service->curlXmlRpc();
                $folderId = $result['id'] ?? null;
            }

            return $folderId;
        });

    }

    /**
     * @notes folderGroupCreate
     * @return int|null
     * @throws IthenticateHttpException
     * @throws exception\IthenticateCurlException
     * @author n
     */
    private function folderGroupCreate():?int
    {
        return $this->executeWithRetry(function (){
            $groupId = $this->getFolderGroupByName($this->serviceConfig['group_name']);
            if (empty($groupId))
            {
                $this->service->setIthenticateMethod(IthenticateEnum::METHOD_GROUP_ADD);

                $params = [
                    'sid' => $this->getSid(),
                    'name' => $this->serviceConfig['group_name']
                ];

                $this->service->setParams($params);

                $result = $this->service->curlXmlRpc();
                $groupId = $result['id'] ?? null;
            }

            return $groupId;
        });
    }

    /**
     * @notes getFolderGroupByName
     * @param string $folderName
     * @return int|null
     * @throws IthenticateHttpException
     * @throws exception\IthenticateCurlException
     * @author n
     */
    private function getFolderByName(string $folderName):?int
    {
        return $this->executeWithRetry(function () use ($folderName){
            $this->service->setIthenticateMethod(IthenticateEnum::METHOD_FOLDER_LIST);

            $params = [
                'sid' => $this->getSid(),
            ];

            $this->service->setParams($params);
            $result = $this->service->curlXmlRpc();

            $foldersList = $result["folders"] ?? [];

            return $this->service->getDataId($foldersList,$folderName);
        });
    }

    /**
     * @notes getFolderGroupByName
     * @param string $groupName
     * @return int|null
     * @throws IthenticateHttpException
     * @throws exception\IthenticateCurlException
     * @author n
     */
    private function getFolderGroupByName(string $groupName):?int
    {
        return $this->executeWithRetry(function () use ($groupName){
            $this->service->setIthenticateMethod(IthenticateEnum::METHOD_GROUP_LIST);

            $params = [
                'sid' => $this->getSid(),
            ];

            $this->service->setParams($params);
            $result = $this->service->curlXmlRpc();

            $groupList = $result["groups"] ?? [];

            return $this->service->getDataId($groupList,$groupName);
        });

    }

    /**
     * @notes executeWithRetry
     * @param callable $callback
     * @param int $retry
     * @return mixed
     * @throws IthenticateHttpException
     * @throws exception\IthenticateCurlException
     * @author n
     * @date 2025/8/19
     */
    private function executeWithRetry(callable $callback, int $retry = 0)
    {
        try {
            return $callback();
        } catch (IthenticateAuthException $e) {
            if ($retry >= 1) {
                throw $e;
            }
            $this->refreshToken();
            return $this->executeWithRetry($callback, $retry + 1);
        } catch (\Exception $e)
        {
            throw $e;
        }
    }
}