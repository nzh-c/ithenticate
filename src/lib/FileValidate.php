<?php
/**
 * Created by PhpStorm.
 * User: Ning
 */

namespace NzhC\Ithenticate\lib;


use NzhC\Ithenticate\enum\IthenticateEnum;
use NzhC\Ithenticate\exception\IthenticateFileException;

class FileValidate
{
    private const MAX_FILE_SIZE     = 100 * 1024 * 1024;

    private const ALLOWED_TYPES    = [
        'doc', 'docx', 'txt', 'ps', 'pdf', 'html', 'xls', 'xlsx',
        'ppt', 'pptx', 'wpd', 'odt', 'rtf', 'hwp'
    ];

    private string $filePath;

    private string $ext;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->ext = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));
        $this->fileExists();
    }

    /**
     * @notes maxSize
     * @author n
     * @throws IthenticateFileException
     */
    public function maxSize()
    {
        $fileSize = filesize($this->filePath);

        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new IthenticateFileException(IthenticateEnum::FILE_SIZE_MAX_100);
        }
    }

    /**
     * @notes allowedTypes
     * @return bool
     * @author n
     * @throws IthenticateFileException
     */
    public function allowedTypes():bool
    {
        if (!in_array($this->ext, self::ALLOWED_TYPES)) {
            throw new IthenticateFileException(IthenticateEnum::FILE_FORMAT_NOT_SUPPORTED);
        }

        return true;
    }

    /**
     * @notes fileExists
     * @return bool
     * @author n
     * @throws IthenticateFileException
     */
    public function fileExists():bool
    {
        if (empty($this->filePath) || !file_exists($this->filePath))
        {
            throw new IthenticateFileException(IthenticateEnum::FILE_NOT_EXIST);
        }

        return true;
    }
}