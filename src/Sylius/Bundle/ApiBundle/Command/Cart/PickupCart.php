<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\ApiBundle\Command\Cart;

use Sylius\Bundle\ApiBundle\Command\ShopUserIdAwareInterface;
use Sylius\Bundle\ApiBundle\Command\ChannelCodeAwareInterface;

/** @experimental */
class PickupCart implements ChannelCodeAwareInterface, ShopUserIdAwareInterface
{
    /** @var string|null
     * @psalm-immutable */
    public $tokenValue;

    /**
     * @var string|null
     * @psalm-immutable
     */
    public $localeCode;

    /** @var string|null */
    private $channelCode;

    /** @var mixed|null */
    public $shopUserId;

    public function __construct(?string $tokenValue = null, ?string $localeCode = null)
    {
        $this->tokenValue = $tokenValue;
        $this->localeCode = $localeCode;
    }

    public function getChannelCode(): ?string
    {
        return $this->channelCode;
    }

    public function setChannelCode(?string $channelCode): void
    {
        $this->channelCode = $channelCode;
    }

    public function getShopUserId()
    {
        return $this->shopUserId;
    }

    public function setShopUserId($shopUserId): void
    {
        $this->shopUserId = $shopUserId;
    }
}
