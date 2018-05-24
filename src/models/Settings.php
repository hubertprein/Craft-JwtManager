<?php
/**
 * JWT Manager for Craft.
 *
 * @author    Hubert Prein
 * @copyright Copyright (c) 2018
 * @package   JwtManager
 * @since     1.0.0
 */

namespace hubertprein\jwtmanager\models;

use craft\base\Model;

/**
 * Settings model.
 */
class Settings extends Model
{
    /**
     * @var string
     */
    public $secretKeyFormat = '{{ siteName }}_login';

    /**
     * @var string
     */
    public $tokensExpireAfter = '+1 day';

    /**
     * @var bool
     */
    public $refreshTokens = true;

    /**
     * @var string
     */
    public $refreshTokensExpireAfter = '+2 weeks';

    /**
     * Returns the validation rules for attributes.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            [['secretKeyFormat', 'tokensExpireAfter', 'refreshTokensExpireAfter'], 'string'],
            ['refreshTokens', 'boolean']
        ];
    }
}
