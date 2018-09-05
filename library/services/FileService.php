<?php
/**
 * 文件管理。
 * @author fingerQin
 * @date 2016-02-24
 */

namespace services;

use finger\Validator;
use common\YCore;
use models\Files;
use models\Admin;
use models\User;

class FileService extends AbstractService
{
    /**
     * 获取文件列表。
     *
     * @param  int      $userType     用户类型：－1全部、1管理员、2普通用户 。
     * @param  string   $userName     用户名。如果user_type为1的时候为管理员用户名，为2的时候为普通用户用户名。
     * @param  string   $fileMd5      文件md5值。
     * @param  int      $fileType     文件类型：1-图片、2-其他文件。
     * @param  string   $startTime    文件上传时间开始。
     * @param  string   $endTime      文件上传时间截止。
     * @param  int      $page         当前页码。
     * @param  int      $count        每页显示条数。
     * @return array
     */
    public static function getFileList($userType = -1, $userName = '', $fileMd5 = '', $fileType = -1, $startTime = '', $endTime = '', $page = -1, $count = 20)
    {
        $userid = Admin::NONE;
        switch ($userType) {
            case 1:
                $AdminModel = new Admin();
                $admin      = $AdminModel->fetchOne([], ['username' => $userName]);
                $userid     = $admin ? $admin['admin_id'] : Admin::NONE;
                break;
            case 2:
                $UserModel = new User();
                $user      = $UserModel->fetchOne([], ['username' => $userName]);
                $userid    = $user ? $user['user_id'] : Admin::NONE;
                break;
        } 
        if (strlen($startTime) > 0 && !Validator::is_date($startTime, 'Y-m-d H:i:s')) {
            YCore::exception(STATUS_ERROR, '开始时间查询有误');
        }
        if (strlen($endTime) > 0 && !Validator::is_date($endTime, 'Y-m-d H:i:s')) {
            YCore::exception(STATUS_ERROR, '结束时间查询有误');
        }
        $FilesModel = new Files();
        $result     = $FilesModel->getList($userType, $userid, $fileMd5, $fileType, $startTime, $endTime, $page, $count);
        foreach ($result['list'] as $key => $item) {
            $item['file_type_label'] = $item['file_type'] == 1 ? '图片' : '其他文件';
            $item['user_name']       = '-';
            $item['user_type_label'] = '-';
            if ($item['user_type'] == 1) {
                $AdminModel             = new Admin();
                $admin                   = $AdminModel->fetchOne([], ['admin_id' => $item['user_id']]);
                $item['user_name']       = $admin ? "{$admin['realname']}[{$admin['username']}]" : '';
                $item['user_type_label'] = '管理员';
            } else if ($item['user_type'] == 2) {
                $UserModel = new User();
                $user = $UserModel->fetchOne([], ['user_id' => $item['user_id']]);
                $item['user_name']       = $user ? "{$user['username']}" : '';
                $item['user_type_label'] = '普通用户';
            }
            $item['created_time'] = YCore::formatDateTime($item['created_time']);
            $result['list'][$key] = $item;
        }
        return $result;
    }

    /**
     * 文件删除。
     *
     * @param  int  $fileId  文件ID。
     * @param  int  $adminId 管理员ID。
     * @return void
     */
    public static function deleteFile($fileId, $adminId)
    {
        $FilesModel = new Files();
        $file = $FilesModel->fetchOne([], ['file_id' => $fileId, 'status' => Files::STATUS_NORMAL]);
        if (empty($file)) {
            YCore::exception(STATUS_ERROR, '文件不存在或已经删除');
        }
        $ok = $FilesModel->deleteFile($fileId);
        if (!$ok) {
            YCore::exception(STATUS_SERVER_ERROR, '服务器繁忙,请稍候重试');
        }
    }
}
