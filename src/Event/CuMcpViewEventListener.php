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

use BaserCore\Event\BcViewEventListener;
use Cake\Event\Event;

/**
 * CuMcp View Event Listener
 */
class CuMcpViewEventListener extends BcViewEventListener
{

    /**
     * events
     * @var string[]
     */
    public $events = [
        //'Users.beforeRender',
    ];

    /**
     * users before render
     * @param Event $event
     */
    /*public function usersBeforeRender(Event $event): void
    {
        // Add your logic here
    }*/

}