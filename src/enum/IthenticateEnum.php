<?php
/**
 * Created by PhpStorm.
 * User: Ning
 */

namespace NzhC\Ithenticate\enum;


class IthenticateEnum
{
    const UPLOAD_TAG_QUERY = '//member[name="upload"]/value/base64';

    const METHOD_DOCUMENT_ADD = 'document.add';

    const METHOD_LOGIN = 'login';

    const METHOD_FOLDER_ADD = 'folder.add';

    const METHOD_FOLDER_LIST = 'folder.list';

    const METHOD_DOCUMENT_GET = 'document.get';

    const METHOD_GROUP_ADD = 'group.add';

    const METHOD_GROUP_LIST = 'group.list';

    const SUCCESSFUL_RESPONSE = 200;

    const UNAUTHENTICATED = 401;

    const UNAUTHORIZED_ACCESS = 403;

    const RESOURCE_NOT_FOUND = 404;

    const REQUEST_ERROR = 500;

    const SYSTEM_ERROR = 'System error';

    const FAILED_FOLDER_GROUP = 'Failed to retrieve the folder group.';

    const FAILED_CREATE_FOLDER_GROUP = 'Failed to create the folder group.';

    const FAILED_REPLACING_LINE_BREAK = 'An error occurred while replacing line breaks in the upload parameter.';

    const FILE_NOT_EXIST = 'The file does not exist.';

    const FILE_SIZE_MAX_100 = 'The file size cannot exceed 100M.';

    const FILE_FORMAT_NOT_SUPPORTED = 'The uploaded file format is not supported.';
}