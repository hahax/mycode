<?php
/**
 * Created by PhpStorm.
 * Date: 2018/3/22 0022
 * Time: 上午 9:57
 */
class carcode1{
    /**
     * @param $num
     * @return string
     * 数组转大写金额
     */
    function get_amount($num){
        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $c2 = "分角元拾佰仟万拾佰仟亿";
        $num = round($num, 2);
        $num = $num * 100;
        if (strlen($num) > 10) {
            return "数据过长";
        }
        $i = 0;
        $c = "";
        while (1) {
            if ($i == 0) {
                $n = substr($num, strlen($num)-1, 1);
            } else {
                $n = $num % 10;
            }
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            $num = $num / 10;
            $num = (int)$num;
            if ($num == 0) {
                break;
            }
        }
        $j = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            $m = substr($c, $j, 6);
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j-3;
                $slen = $slen-3;
            }
            $j = $j + 3;
        }

        if (substr($c, strlen($c)-3, 3) == '零') {
            $c = substr($c, 0, strlen($c)-3);
        }
        if (empty($c)) {
            return "零元整";
        }else{
            return $c . "整";
        }
    }

    /**
     * 友好的时间显示
     *
     * @param int    $sTime 待显示的时间
     * @param string $type  类型. normal | mohu | full | ymd | other
     * @param string $alt   已失效
     * @return string
     */
    function friendlyDate($sTime,$type = 'normal',$alt = 'false') {
        if (!$sTime)
            return '';
        //sTime=源时间，cTime=当前时间，dTime=时间差
        $cTime      =   time();
        $dTime      =   $cTime - $sTime;
        $dDay       =   intval(date("z",$cTime)) - intval(date("z",$sTime));
        //$dDay     =   intval($dTime/3600/24);
        $dYear      =   intval(date("Y",$cTime)) - intval(date("Y",$sTime));
        //normal：n秒前，n分钟前，n小时前，日期
        if($type=='normal'){
            if( $dTime < 60 ){
                if($dTime < 10){
                    return '刚刚';    //by yangjs
                }else{
                    return intval(floor($dTime / 10) * 10)."秒前";
                }
            }elseif( $dTime < 3600 ){
                return intval($dTime/60)."分钟前";
                //今天的数据.年份相同.日期相同.
            }elseif( $dYear==0 && $dDay == 0  ){
                //return intval($dTime/3600)."小时前";
                return '今天'.date('H:i',$sTime);
            }elseif($dYear==0){
                return date("m月d日 H:i",$sTime);
            }else{
                return date("Y-m-d H:i",$sTime);
            }
        }elseif($type=='mohu'){
            if( $dTime < 60 ){
                return $dTime."秒前";
            }elseif( $dTime < 3600 ){
                return intval($dTime/60)."分钟前";
            }elseif( $dTime >= 3600 && $dDay == 0  ){
                return intval($dTime/3600)."小时前";
            }elseif( $dDay > 0 && $dDay<=7 ){
                return intval($dDay)."天前";
            }elseif( $dDay > 7 &&  $dDay <= 30 ){
                return intval($dDay/7) . '周前';
            }elseif( $dDay > 30 ){
                return intval($dDay/30) . '个月前';
            }
            //full: Y-m-d , H:i:s
        }elseif($type=='full'){
            return date("Y-m-d , H:i:s",$sTime);
        }elseif($type=='ymd'){
            return date("Y-m-d",$sTime);
        }else{
            if( $dTime < 60 ){
                return $dTime."秒前";
            }elseif( $dTime < 3600 ){
                return intval($dTime/60)."分钟前";
            }elseif( $dTime >= 3600 && $dDay == 0  ){
                return intval($dTime/3600)."小时前";
            }elseif($dYear==0){
                return date("Y-m-d H:i:s",$sTime);
            }else{
                return date("Y-m-d H:i:s",$sTime);
            }
        }
    }

    /**
     * 上传图片
     * @return mixed
     */
    public function upload_pic()
    {
        $id = Input::get("pid");
        \DB::beginTransaction();
        $photo = \Input::file("file");
        try {
            $copyId = PicList::lockForUpdate()->find($id);
            $this->validPic($photo);
            $name = $this->makeNameForPic($photo);
            copy($_FILES['file']["tmp_name"], public_path() . "/upload/" . $name);
            $url = "/upload/" . $name;

            $result = $copyId->addZdyPic($url);
            $copyId->save();
            \DB::commit();

            return $this->responseWithJson(array("success" => true, "ret" => $result));
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->responseWithJson(array("success" => false, 'msg' => $photo->getClientOriginalName() . '上传失败'));
        }
    }

    public function addZdyPic($path)
    {

        $picArr = json_decode($this->zdypics, true) ?: ['count' => 0, 'index' => 0, 'data' => []];

        $picArr['count'] += 1;
        $picArr['index'] += 1;
        $picArr['data'][$picArr['index']] = [
            'id' => $picArr['index'],
            'status' => 1,
            'path' => $path
        ];

        if ($picArr['count'] > 30) {
            throw new \RestException("最多上传30张图片");
        }
        $this->zdypics = json_encode($picArr);
        $this->zdypics_num += 1;

        return [
            'pid' => $this->id,
            'oid' => $picArr['index'],
            'path' => $path
        ];
    }

    /**
     * 验证图片
     * @param $photo
     * @return bool
     */
    private function validPic($photo)
    {
        if (!in_array(strtolower($photo->getClientOriginalExtension()), array('jpg', 'jpeg', 'bmp', 'gif', 'png'))) {
            throw new \NotAuthException("图片格式不对只支持jpg，jpeg，bmp，gif");
        }
        return true;
    }

    /**
     * 复制目录
     * @param $src 源目录
     * @param $dst 目标目录
     */
    function copy_dir($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    copy_dir($src . '/' . $file,$dst . '/' . $file);
                    continue;
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * 复制目录下除图片外其他文件
     * @param $src
     * @param $dst
     */
    public function copy_dir_nopic($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->copy_dir($src . '/' . $file,$dst . '/' . $file);
                    continue;
                }
                else {
                    if($this->isImg($file))
                    {
                        continue;
                    }
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function isImg($file)
    {
        $arr = ['.jpeg','.jpg','.png'];
        $fix = strtolower(strrchr($file, '.'));
        if(in_array($fix,$arr))
        {
            return true;
        }else
        {
            return false;
        }
    }

    /**
     * 获取系统，win下对中文路径、文件夹进行iconv转义
     * @param $path
     * @return string
     */
    public function getOs($path)
    {
        $osPath = PATH_SEPARATOR==';'? iconv("utf-8","gbk",$path) : $path;
        return $osPath;
    }

    /**
     * 迭代删除文件夹
     * @param $path
     * @return bool
     */
    function rmdirs($path)
    {
        /* 初始化条件 */
        $stack = array();
        if (!file_exists($path)) return false;
        $path = realpath($path) . '/';
        array_push($stack, '');
        /* 迭代条件 */
        while (count($stack) !== 0) {
            $dir = end($stack);
            $items = scandir($path . $dir);
            /* 执行过程 */
            if (count($items) === 2) {
                rmdir($path . $dir);
                array_pop($stack);
                continue;
            }
            /* 执行过程 */
            foreach ($items as $item) {
                if ($item == '.' || $item == '..') continue;
                $_path = $path . $dir . $item;
                if (is_file($_path)) unlink($_path);
                /* 更新条件 */
                if (is_dir($_path)) array_push($stack, $dir . $item . '/');
            }
        }
        return !(file_exists($path));
    }

    /**
     * 删除目录及目录下所有文件或删除指定文件，只能删二级目录
     * @param str $path   待删除目录路径
     * @param int $delDir 是否删除目录，1或true删除目录，0或false则只删除文件保留目录（包含子目录）
     * @return bool 返回删除状态
     */
    function delDirAndFile($path, $delDir = FALSE) {
        $handle = opendir($path);
        if ($handle) {
            while (false !== ( $item = readdir($handle) )) {
                if ($item != "." && $item != "..")
                    is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
            }
            closedir($handle);
            if ($delDir)
                return rmdir($path);
        }else {
            if (file_exists($path)) {
                return unlink($path);
            } else {
                return FALSE;
            }
        }
    }

    /**
     * desription 压缩图片
     * @param sting $imgsrc 图片路径
     * @param string $imgdst 压缩后保存路径
     * $w 图片宽
     * $h 图片高度
     */
    function image_png_size_add($imgsrc, $imgdst,$w,$h)
    {
        list($width, $height, $type) = getimagesize($imgsrc);
        $new_width = $width>600?$w:$width;
        $new_height = $height>600?$h:$height;
        switch ($type) {
            case 1:
                $giftype = check_gifcartoon($imgsrc);
                if ($giftype) {
                    header('Content-Type:image/gif');
                    $image_wp = imagecreatetruecolor($new_width, $new_height);
                    $image = imagecreatefromgif($imgsrc);
                    imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagejpeg($image_wp, $imgdst, 75);
                    imagedestroy($image_wp);
                }
                break;
            case 2:
                header('Content-Type:image/jpeg');
                $image_wp = imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefromjpeg($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst, 75);
                imagedestroy($image_wp);
                break;
            case 3:
                header('Content-Type:image/png');
                $image_wp = imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefrompng($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst, 75);
                imagedestroy($image_wp);
                break;
        }
    }

    /**
     * desription 判断是否gif动画
     * @param sting $image_file图片路径
     * @return boolean t 是 f 否
     */
    function check_gifcartoon($image_file)
    {
        $fp = fopen($image_file, 'rb');
        $image_head = fread($fp, 1024);
        fclose($fp);
        return preg_match("/" . chr(0x21) . chr(0xff) . chr(0xb) . 'NETSCAPE2.0' . "/", $image_head) ? false : true;
    }
}



