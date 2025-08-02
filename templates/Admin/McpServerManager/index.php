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
 * MCPサーバー管理画面
 */
?>

<div class="bca-main">
    <div class="bca-main__contents">
        <div class="bca-main__body">

            <!-- サーバー状態表示 -->
            <div class="bca-panel-box">
                <div class="bca-panel-box__title">MCPサーバー状態</div>
                <div class="bca-panel-box__body">

                    <div class="bca-data-list">
                        <div class="bca-data-list__item">
                            <div class="bca-data-list__item-label">状態</div>
                            <div class="bca-data-list__item-value">
                                <?php if ($status['running']): ?>
                                    <span class="bca-label bca-label--success">稼働中</span>
                                    <?php if ($status['pid']): ?>
                                        <small>(PID: <?= h($status['pid']) ?>)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="bca-label bca-label--danger">停止中</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="bca-data-list__item">
                            <div class="bca-data-list__item-label">ChatGPT設定用URL</div>
                            <div class="bca-data-list__item-value">
                                <code><?= h($status['chatgpt_url']) ?></code>
                                <button type="button" class="bca-btn bca-btn--sm" onclick="copyToClipboard('<?= h($status['chatgpt_url']) ?>')">
                                    コピー
                                </button>
                            </div>
                        </div>

                        <div class="bca-data-list__item">
                            <div class="bca-data-list__item-label">内部URL</div>
                            <div class="bca-data-list__item-value">
                                <code><?= h($status['internal_url']) ?></code>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- コントロールパネル -->
            <div class="bca-panel-box">
                <div class="bca-panel-box__title">サーバー操作</div>
                <div class="bca-panel-box__body">

                    <div class="bca-btn-group">
                        <?php if ($status['running']): ?>
                            <?= $this->BcAdminForm->postLink(
                                '停止',
                                ['action' => 'stop'],
                                [
                                    'class' => 'bca-btn bca-btn--danger',
                                    'confirm' => 'MCPサーバーを停止しますか？'
                                ]
                            ) ?>

                            <?= $this->BcAdminForm->postLink(
                                '再起動',
                                ['action' => 'restart'],
                                [
                                    'class' => 'bca-btn bca-btn--warning',
                                    'confirm' => 'MCPサーバーを再起動しますか？'
                                ]
                            ) ?>
                        <?php else: ?>
                            <?= $this->BcAdminForm->postLink(
                                '起動',
                                ['action' => 'start'],
                                [
                                    'class' => 'bca-btn bca-btn--success'
                                ]
                            ) ?>
                        <?php endif; ?>

                        <?= $this->Html->link(
                            '設定',
                            ['action' => 'configure'],
                            ['class' => 'bca-btn bca-btn--default']
                        ) ?>
                    </div>

                </div>
            </div>

            <!-- 使用方法 -->
            <div class="bca-panel-box">
                <div class="bca-panel-box__title">ChatGPTでの設定方法</div>
                <div class="bca-panel-box__body">

                    <div class="bca-data-list">
                        <div class="bca-data-list__item">
                            <div class="bca-data-list__item-label">手順1</div>
                            <div class="bca-data-list__item-value">
                                上記の「起動」ボタンでMCPサーバーを起動してください
                            </div>
                        </div>

                        <div class="bca-data-list__item">
                            <div class="bca-data-list__item-label">手順2</div>
                            <div class="bca-data-list__item-value">
                                ChatGPTの設定画面で「MCPサーバー」を追加し、上記のURLを設定してください
                            </div>
                        </div>

                        <div class="bca-data-list__item">
                            <div class="bca-data-list__item-label">手順3</div>
                            <div class="bca-data-list__item-value">
                                ChatGPTから「ブログ記事を追加して」などの指示でbaserCMSを操作できます
                            </div>
                        </div>
                    </div>

                    <div class="bca-section">
                        <h4>利用可能な機能</h4>
                        <ul>
                            <li>ブログ記事の追加・編集・削除</li>
                            <li>カスタムエントリーの追加・取得</li>
                            <li>サーバー情報の取得</li>
                        </ul>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('URLをクリップボードにコピーしました');
    }, function(err) {
        console.error('コピーに失敗しました: ', err);
        // フォールバック
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('URLをクリップボードにコピーしました');
    });
}

// 自動リロード（ステータス確認用）
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 30000); // 30秒ごと
</script>
