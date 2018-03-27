<?php 

class Mycode{
	//二维数组值首字母大写
	public function test()
    {
        $array = [
            ['name' => 'shengj', 'sex' => 'male'],
            ['name' => 'wangm', 'sex' => 'male'],
        ];
        $this->arrayToUp($array);
    }

    public function arrayToUp(array $array)
    {
        $arr = [];
        foreach ($array as $k => $v)
        {
            $keys = array_keys($v);
            $vals = array_values($v);
            foreach ($keys as $key => $val) {
                $arr[$k][$key] = ucfirst($vals[$key]);
            }
        }
        return $arr;
    }
}