<?php

declare(strict_types=1);

namespace reactivestudio\filestorage\uploaders;

use Exception;
use reactivestudio\filestorage\exceptions\StorageException;
use reactivestudio\filestorage\exceptions\UploaderException;
use reactivestudio\filestorage\helpers\StorageHelper;
use reactivestudio\filestorage\models\form\FileForm;
use reactivestudio\filestorage\uploaders\base\AbstractUploader;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use Yii;

class RemoteUploader extends AbstractUploader
{
    public const URL_CONFIG_NAME = 'url';

    /**
     * {@inheritDoc}
     */
    public function buildForm(string $fileEntityClass, array $config = []): FileForm
    {
        $form = parent::buildForm($fileEntityClass);

        try {
            $url = ArrayHelper::getValue($config, static::URL_CONFIG_NAME, '');
        } catch (Exception $e) {
            Yii::warning($e->getMessage());
            $url = '';
        }

        $form->uploadFile = $this->buildUploadedFile($url);

        return $form;

    }

    /**
     * @param string $url
     * @return UploadedFile
     * @throws UploaderException
     */
    private function buildUploadedFile(string $url): UploadedFile
    {
        try {
            $tempFilePath = StorageHelper::downloadFile($url);
        } catch (StorageException $e) {
            throw new UploaderException("Download file error: {$e->getMessage()}", 0, $e);
        }

        try {
            $uploadFile = Yii::createObject(UploadedFile::class);
        } catch (InvalidConfigException $e) {
            throw new UploaderException("Error with creating UploadFile: {$e->getMessage()}", 0, $e);
        }

        $uploadFile->tempName = $tempFilePath;
        $uploadFile->name = basename($url);
        $uploadFile->size = filesize($tempFilePath);
        $uploadFile->type = FileHelper::getMimeTypeByExtension($uploadFile->name);
        $uploadFile->error = UPLOAD_ERR_OK;

        return $uploadFile;
    }
}
