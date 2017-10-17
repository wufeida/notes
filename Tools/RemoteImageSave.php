<?php

/**
 * 远程图片下载到本地
 * @param $path 远程url地址
 * @param $img_path 本地保存路径
 * @return bool|string
 */
function saveImage($path, $img_path) {
    if(!preg_match('/\/([^\/]+)\.[a-z]{3,4}$/i',$path,$matches)) return false;
    $image_name = strToLower($matches[1]);
    $image_name = $image_name.'.png';
    $ch = curl_init ($path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $img = curl_exec ($ch);
    curl_close ($ch);
    if(!file_exists($img_path))
        mkdir($img_path);
    $fp = fopen($img_path.'/'.$image_name,'w');
    fwrite($fp, $img);
    fclose($fp);
    return '/'.$img_path.'/'.$image_name;
}