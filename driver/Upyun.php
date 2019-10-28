<?php

namespace qingxiaoyun\upload\driver;

use Upyunvalite;
use qingxiaoyun\upload\Uploader;

class Upyun
{
    protected $file;

    const ORIGINAL = true;

    protected $fileType = 'files';

    protected $config = [
        'original' => self::ORIGINAL
    ];

    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function multiple(...$names)
    {
        $files = [];
        foreach ($names as $key => $name) {
            if (!$this->has($name)) {
                continue;
            }
            $files[] = $name;
        }

        $files = call_user_func_array([$this->localDriver(), 'multiple'], $files);

        $multiple = [];
        foreach ($files as $key => $file) {
            $multiple[$key] = [
                'save_name' => \Arr::get($this->uploadFile(
                    $path = '.' . ltrim($file['save_name']), '.'), 'imgurl'
                ),
            ];
        }

        return $multiple;
    }

    public function image($name)
    {
        $this->fileType = 'images';

        if (!$this->has($name)) {
            return false;
        }

        if ($image = call_user_func_array([$this->localDriver(), 'image'], [ $name ])) {
            $path = '.' . ltrim($image->getUrlPath(), '.');
        }

        $this->file = $this->uploadFile($path);

        return $this;
    }

    public function video($name)
    {
        $this->fileType = 'videos';

        if (!$this->has($name)) {
            return false;
        }

        if ($image = call_user_func_array([$this->localDriver(), 'video'], [ $name ])) {
            $path = '.' . ltrim($image->getUrlPath(), '.');
        }

        $this->file = $this->uploadFile($path);

        return $this;
    }

    public function has($name)
    {
        return isset($_FILES[$name]) && 0 == $_FILES[$name]['error'];

        try {
            return request()->has($name, 'file');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function uploadFile(string $path, bool $original = Upyun::ORIGINAL)
    {
        if(! file_exists($path)){
            throw new \Exception('文件不存在');
        }

        $result = app(Upyunvalite::class)->upyunUpload(
            $path, $this->getRootPath(), pathinfo($path, PATHINFO_BASENAME), $this->config['original']
        );

        if(isset($result['error'])){
            throw new \Exception($result['error']);
        }

        return $result;
    }

    public function localDriver()
    {
        return app(Uploader::class)->driver('local');
    }

    public function getRootPath()
    {
        return $this->config['root'] . $this->fileType . '/'. date('Ymd');
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getUrlPath()
    {
        if (!$this->file) {
            return '';
        }

        return $this->file['imgurl'];
    }
}
