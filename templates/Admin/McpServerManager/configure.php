<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.7
 * @license       https://basercms.net/license/index.html MIT License
 */

/**
 * MCPサーバー設定画面
 */
?>

<div class="bca-main">
    <div class="bca-main__contents">
        <div class="bca-main__body">

            <?= $this->BcAdminForm->create(null, ['novalidate' => true]) ?>

            <div class="bca-panel-box">
                <div class="bca-panel-box__title">MCPサーバー設定</div>
                <div class="bca-panel-box__body">

                    <div class="bca-form-table">
                        <div class="bca-form-table__row">
                            <div class="bca-form-table__head">
                                ホスト
                            </div>
                            <div class="bca-form-table__col">
                                <?= $this->BcAdminForm->control('host', [
                                    'type' => 'text',
                                    'value' => $config['host'],
                                    'class' => 'bca-textbox',
                                    'help' => '通常は変更不要です（127.0.0.1）'
                                ]) ?>
                            </div>
                        </div>

                        <div class="bca-form-table__row">
                            <div class="bca-form-table__head">
                                ポート
                            </div>
                            <div class="bca-form-table__col">
                                <?= $this->BcAdminForm->control('port', [
                                    'type' => 'number',
                                    'value' => $config['port'],
                                    'class' => 'bca-textbox',
                                    'min' => 1024,
                                    'max' => 65535,
                                    'help' => '内部通信用ポート（デフォルト: 3000）'
                                ]) ?>
                            </div>
                        </div>

                        <div class="bca-form-table__row">
                            <div class="bca-form-table__head">
                                自動起動
                            </div>
                            <div class="bca-form-table__col">
                                <?= $this->BcAdminForm->control('auto_start', [
                                    'type' => 'checkbox',
                                    'checked' => $config['auto_start'],
                                    'label' => 'baserCMS起動時にMCPサーバーも自動起動する',
                                    'help' => '有効にすると、Webサーバー起動時に自動でMCPサーバーも起動します'
                                ]) ?>
                            </div>
                        </div>

                        <div class="bca-form-table__row">
                            <div class="bca-form-table__head">
                                ログレベル
                            </div>
                            <div class="bca-form-table__col">
                                <?= $this->BcAdminForm->control('log_level', [
                                    'type' => 'select',
                                    'options' => [
                                        'error' => 'エラーのみ',
                                        'warning' => '警告以上',
                                        'info' => '情報以上',
                                        'debug' => 'デバッグ情報も含む'
                                    ],
                                    'value' => $config['log_level'],
                                    'class' => 'bca-select',
                                    'help' => 'ログに出力する情報のレベルを設定'
                                ]) ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="bca-panel-box">
                <div class="bca-panel-box__title">高度な設定</div>
                <div class="bca-panel-box__body">

                    <div class="bca-form-table">
                        <div class="bca-form-table__row">
                            <div class="bca-form-table__head">
                                外部アクセスURL
                            </div>
                            <div class="bca-form-table__col">
                                <?php
                                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                $externalUrl = "{$protocol}://{$host}/cu-mcp/mcp-proxy";
                                ?>
                                <div class="bca-data-list">
                                    <div class="bca-data-list__item">
                                        <div class="bca-data-list__item-label">ChatGPT用URL</div>
                                        <div class="bca-data-list__item-value">
                                            <code><?= h($externalUrl) ?></code>
                                        </div>
                                    </div>
                                    <div class="bca-data-list__item">
                                        <div class="bca-data-list__item-label">内部URL</div>
                                        <div class="bca-data-list__item-value">
                                            <code>http://<?= h($config['host']) ?>:<?= h($config['port']) ?></code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="bca-actions">
                <div class="bca-actions__main">
                    <?= $this->BcAdminForm->submit('保存', [
                        'div' => false,
                        'class' => 'btn-red bca-btn bca-loading',
                        'data-bca-btn-type' => 'save',
                        'data-bca-btn-size' => 'lg',
                        'data-bca-btn-width' => 'lg',
                        'id' => 'BtnSave'
                    ]) ?>
                </div>
                <div class="bca-actions__sub">
                    <?= $this->Html->link('キャンセル', ['action' => 'index'], [
                        'class' => 'bca-btn',
                        'data-bca-btn-type' => 'cancel',
                        'data-bca-btn-size' => 'lg',
                        'data-bca-btn-width' => 'lg'
                    ]) ?>
                </div>
            </div>

            <?= $this->BcAdminForm->end() ?>

        </div>
    </div>
</div>

<script>
// フォーム送信前の確認
document.getElementById('BtnSave').addEventListener('click', function(e) {
    const port = document.querySelector('input[name="port"]').value;
    if (port < 1024 || port > 65535) {
        e.preventDefault();
        alert('ポート番号は1024〜65535の範囲で指定してください');
        return false;
    }
});
</script>
