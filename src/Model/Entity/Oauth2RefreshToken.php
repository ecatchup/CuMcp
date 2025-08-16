<?php
declare(strict_types=1);

namespace CuMcp\Model\Entity;

use Cake\ORM\Entity;

/**
 * Oauth2RefreshToken Entity
 *
 * @property int $id
 * @property string $token_id
 * @property string $access_token_id
 * @property bool $revoked
 * @property \Cake\I18n\DateTime $expires_at
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Oauth2RefreshToken extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'token_id' => true,
        'access_token_id' => true,
        'revoked' => true,
        'expires_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
