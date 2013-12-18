<?php
namespace WakePHP\Actions\Account;

use WakePHP\Actions\Generic;
use PHPDaemon\Request\Generic as Request;
use PHPDaemon\Core\ComplexJob;
use PHPDaemon\Core\Daemon;
use PHPDaemon\Core\Debug;
use PHPDaemon\Clients\Mongo\Cursor;
use WakePHP\Core\Request as WakePHPRequest;

/**
 * Class ManageAccountsGet
 * @package WakePHP\Actions\Account
 * @dynamic_fields
 */
class ManageAccountsGet extends Generic
{

    public function perform()
    {
        $this->cmp->onAuth(function ($result) {

            if (!in_array('Superusers', $this->req->account['aclgroups'], true)) {
                $this->req->setResult(['success' => false, 'goLoginPage' => true]);
                return;
            }

            $this->req->appInstance->accounts->getAccount()->condSetId(Request::getString($_REQUEST['id']))
                ->fields(['name', 'email', 'credentials.username'])
                ->fetch(function ($item) {
                    $this->req->setResultObj(['success' => true, 'item' => $item->toArray()]);
                });

        });
    }
}
