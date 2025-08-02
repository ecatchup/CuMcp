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

namespace CuMcp\View\Helper;

use BaserCore\View\Helper\BcPluginBaserHelperInterface;
use Cake\View\Helper;

/**
 * CuMcp Baser Helper
 */
class CuMcpBaserHelper extends Helper implements BcPluginBaserHelperInterface
{

    /**
     * Helper
     * @var array
     */
    //public array $helpers = ['CuMcp.CuMcp'];

    /**
     * Method
     * @return array[]
     */
    public function methods(): array
    {
        return [
            //'getCuMcpIndex' => ['CuMcp', 'getIndex'],
        ];
    }

}
