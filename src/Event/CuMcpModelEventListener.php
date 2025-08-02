<?php
declare(strict_types=1);
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.7
 * @license       https://basercms.net/license/index.html MIT License
 */

namespace CuMcp\Event;

use BaserCore\Event\BcModelEventListener;
use Cake\Event\Event;

/**
 * CuMcp Model Event Listener
 */
class CuMcpModelEventListener extends BcModelEventListener
{

    /**
     * events
     * @var string[]
     */
    public $events = [
        //'Users.beforeFind',
    ];

    /**
     * users before find
     * @param Event $event
     */
    /*public function usersBeforeFind(Event $event): void
    {
        // Add your logic here
    }*/

}