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

namespace Sylius\Behat\Context\Api\Admin;

use Behat\Behat\Context\Context;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Component\Core\Model\PromotionInterface;
use Webmozart\Assert\Assert;

final class ManagingPromotionsContext implements Context
{
    /** @var ApiClientInterface */
    private $client;

    /** @var ResponseCheckerInterface */
    private $responseChecker;

    public function __construct(
        ApiClientInterface $client,
        ResponseCheckerInterface $responseChecker
    ) {
        $this->client = $client;
        $this->responseChecker = $responseChecker;
    }

    /**
     * @Given I want to browse promotions
     * @When I browse promotions
     */
    public function iWantToBrowsePromotions(): void
    {
        $this->client->index();
    }

    /**
     * @Then I should see a single promotion in the list
     * @Then there should be :amount promotions
     */
    public function thereShouldBePromotion(int $amount = 1): void
    {
        Assert::same(
            count($this->responseChecker->getCollection($this->client->getLastResponse())),
            $amount
        );
    }

    /**
     * @Then the :promotionName promotion should exist in the registry
     */
    public function thePromotionShouldAppearInTheRegistry(string $promotionName): void
    {
        $returnedPromotion = current($this->responseChecker->getCollectionItemsWithValue(
            $this->client->getLastResponse(),
            'name',
            $promotionName
        ));

        Assert::notNull($returnedPromotion, sprintf('There is no promotion %s in registry', $promotionName));
    }

    /**
     * @Then /^(this promotion) should be coupon based$/
     */
    public function thisPromotionShouldBeCouponBased(PromotionInterface $promotion): void
    {

        $returnedPromotion = current($this->responseChecker->getCollectionItemsWithValue(
            $this->client->getLastResponse(),
            'name',
            $promotion->getName()
        ));

        Assert::true(
            $returnedPromotion['couponBased'],
            sprintf('The promotion %s isn\'t coupon based', $promotion->getName())
        );
    }

    /**
     * @Then /^I should be able to manage coupons for (this promotion)$/
     */
    public function iShouldBeAbleToManageCouponsForThisPromotion(PromotionInterface $promotion): void
    {
        $returnedPromotion = current($this->responseChecker->getCollectionItemsWithValue(
            $this->client->getLastResponse(),
            'name',
            $promotion->getName()
        ));

        Assert::keyExists($returnedPromotion, 'coupons');
    }
}
