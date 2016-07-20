<?php

namespace lib;

use Symfony\Component\Yaml\Yaml;

/**
 * Class Storyboard
 * 
 * @package Rogyar/lib
 */
class Storyboard
{
    /** @var  Yaml */
    protected $configurationReader;
    /** @var string  */
    protected $configFilePath = '';
    /** @var  array */
    protected $config = [];
    /** @var string  */
    protected $content = '';
    /** @var string  */
    protected $token = '';

    public function __construct(
        Yaml $configurationReader,
        $configFilePath,
        $token
    )
    {
        $this->configurationReader = $configurationReader;
        $this->configFilePath = $configFilePath;
        $this->token = $token;
    }

    public function getConfig()
    {
        if (empty($this->config)) {
            $configReader = $this->configurationReader;
            if (file_exists($this->configFilePath)) {
                $this->config = $configReader::parse(file_get_contents($this->configFilePath));
            } else {
                throw new \Exception('The configuration path is incorrect');
            }
        }

        return $this->config;
    }

    /**
     * Compares given token with value in configuration
     *
     * @return bool
     */
    public function validateToken()
    {
        $validToken = $this->getConfig()['token'];

        return $validToken == $this->token;
    }

    public function setContent($content)
    {
        return ($this->content = $content);
    }

    public function getContent()
    {
        if (empty($this->content)) {
            $this->content = $this->readContent();
        }
        return $this->content;
    }

    /**
     * Returns path to the writable storage path
     *
     * @return string mixed
     */
    public function getStoragePath()
    {
        $config = $this->getConfig();

        return  $config['storagePath'];
    }

    /**
     * Reads content from the storage and returns it's value
     *
     * @param $escapeJs - if true, all JS from the file contents will be escaped
     * @return string
     */
    public function readContent($escapeJs = false)
    {
        $fileContents = file_get_contents($this->getStoragePath());

        if ($escapeJs) {
            $fileContents = htmlspecialchars($fileContents); //TODO: escape only JS
        }

        return $this->setContent($fileContents);
    }

    /**
     * Writes content to the content storage
     *
     * @param string $content
     * @throws \Exception
     * @return bool
     */
    public function writeContent($content)
    {
        if (!$this->validateToken()) {
            throw new \Exception('The token is invalid');
        }

        $this->checkStorageFilePermissions();
        file_put_contents($this->getStoragePath(), $content);

        return true;
    }

    /**
     * Renders content using a template
     *
     * @return string
     * @throws \Exception
     */
    public function renderTemplate()
    {
        $template = $this->getConfig()['templatePath'];
        if (!file_exists($template)) {
            throw new \Exception('The template path is wrong. Please, check your configuration');
        }

        $templateContent = file_get_contents($template);
        $templateContent = str_replace('<--- content --->', $this->getContent(), $templateContent);

        return $templateContent;
    }

    /**
     * Checks if the storage is writable
     *
     * @throws \Exception
     */
    protected function checkStorageFilePermissions()
    {
        if (!is_writable($this->getStoragePath())) {
            throw new \Exception("The storage file is not writable. Please, check your permissions", 503);
        }
    }
}