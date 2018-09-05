<?php
/**
 * 多进程处理。
 * 
 * @author fingerQin
 * @date 2018-03-05
 */

namespace threads;

use finger\Thread\Thread;

class TestThread extends Thread
{
    /**
     * 业务运行方法。
     * 
     * -- 在 run 中编写的方法请一定要确定是事务型的。要么成功要么失败。要处于好失败情况下的数据处理。
     * 
     * @param int $threadNum 进程数量。
     * @param int $num       当前子进程编号。此编号与当前进程数量对应。比如，你有一个业务需要10个进程处理，每个进行处理其中的10分之一的数量。此时可以根据此值取模。
     * 
     * @return void
     */
    public function run($threadNum, $num)
    {
        while (true) {
            echo $num . "\n";
            $randInt = mt_rand(1, 3);
            sleep($randInt);
        }
    }
}
