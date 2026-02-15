<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

trait FileTrait
{

    /**
     * @return mixed
     */
    public function getUploadDir($path, $create = false)
    {
          // dd($path);
        $path = $this->getParameter('upload_dir') . '/' . $path;
        if ($create && !is_dir($path)) {
            mkdir($path, 0777, true);
        }
     
        return $path;
    }
}
