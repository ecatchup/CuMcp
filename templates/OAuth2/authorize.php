<?php
/**
 * OAuth2 認可画面テンプレート
 */
?>
<div class="oauth2-authorize">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>アプリケーション認可</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong><?= h($client->getName()) ?></strong> が以下の権限を要求しています：
                        </div>

                        <div class="permissions mb-3">
                            <h5>要求されている権限:</h5>
                            <ul>
                                <?php if (empty($scope)): ?>
                                    <li>基本的なアクセス権限</li>
                                <?php else: ?>
                                    <?php foreach (explode(' ', $scope) as $scopeItem): ?>
                                        <li><?= h($this->OAuth2->getScopeDescription($scopeItem)) ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <div class="user-info mb-3">
                            <small class="text-muted">
                                ログインユーザー: <?= h($user->name ?? $user->email ?? 'ユーザー') ?>
                            </small>
                        </div>

                        <?= $this->Form->create(null, ['type' => 'post']) ?>
                        <?= $this->Form->hidden('client_id', ['value' => $clientId]) ?>
                        <?= $this->Form->hidden('redirect_uri', ['value' => $redirectUri]) ?>
                        <?= $this->Form->hidden('scope', ['value' => $scope]) ?>
                        <?= $this->Form->hidden('state', ['value' => $state]) ?>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <?= $this->Form->button('拒否', [
                                'type' => 'submit',
                                'name' => 'action',
                                'value' => 'deny',
                                'class' => 'btn btn-secondary me-md-2'
                            ]) ?>
                            <?= $this->Form->button('許可', [
                                'type' => 'submit',
                                'name' => 'action',
                                'value' => 'approve',
                                'class' => 'btn btn-primary'
                            ]) ?>
                        </div>

                        <?= $this->Form->end() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.oauth2-authorize {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background-color: #f8f9fa;
}

.oauth2-authorize .card {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.oauth2-authorize .card-header {
    background-color: #007bff;
    color: white;
    text-align: center;
}

.oauth2-authorize .permissions ul {
    list-style-type: none;
    padding-left: 0;
}

.oauth2-authorize .permissions li {
    padding: 0.25rem 0;
    border-bottom: 1px solid #eee;
}

.oauth2-authorize .permissions li:before {
    content: "✓ ";
    color: #28a745;
    font-weight: bold;
}
</style>
