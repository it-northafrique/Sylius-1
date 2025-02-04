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

namespace Sylius\Bundle\ApiBundle\CommandHandler\Account;

use Doctrine\Persistence\ObjectManager;
use Sylius\Bundle\ApiBundle\Command\Account\SendAccountRegistrationEmail;
use Sylius\Bundle\ApiBundle\Command\Account\SendAccountVerificationEmail;
use Sylius\Bundle\ApiBundle\Command\Account\RegisterShopUser;
use Sylius\Bundle\ApiBundle\Provider\CustomerProviderInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\User\Security\Generator\GeneratorInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/** @experimental */
final class RegisterShopUserHandler implements MessageHandlerInterface
{
    /** @var FactoryInterface */
    private $shopUserFactory;

    /** @var ObjectManager */
    private $shopUserManager;

    /** @var CustomerProviderInterface */
    private $customerProvider;

    /** @var ChannelRepositoryInterface */
    private $channelRepository;

    /** @var GeneratorInterface */
    private $tokenGenerator;

    /** @var MessageBusInterface */
    private $commandBus;

    public function __construct(
        FactoryInterface $shopUserFactory,
        ObjectManager $shopUserManager,
        CustomerProviderInterface $customerProvider,
        ChannelRepositoryInterface $channelRepository,
        GeneratorInterface $tokenGenerator,
        MessageBusInterface $commandBus
    ) {
        $this->shopUserFactory = $shopUserFactory;
        $this->shopUserManager = $shopUserManager;
        $this->customerProvider = $customerProvider;
        $this->channelRepository = $channelRepository;
        $this->tokenGenerator = $tokenGenerator;
        $this->commandBus = $commandBus;
    }

    public function __invoke(RegisterShopUser $command): ShopUserInterface
    {
        /** @var ShopUserInterface $user */
        $user = $this->shopUserFactory->createNew();
        $user->setPlainPassword($command->password);

        $customer = $this->customerProvider->provide($command->email);

        if ($customer->getUser() !== null) {
            throw new \DomainException(sprintf('User with email "%s" is already registered.', $command->email));
        }

        $customer->setFirstName($command->firstName);
        $customer->setLastName($command->lastName);
        $customer->setSubscribedToNewsletter($command->subscribedToNewsletter);
        $customer->setPhoneNumber($command->phoneNumber);
        $customer->setUser($user);

        /** @var ChannelInterface $channel */
        $channel = $this->channelRepository->findOneByCode($command->channelCode);

        $this->shopUserManager->persist($user);

        $this->commandBus->dispatch(new SendAccountRegistrationEmail(
            $command->email,
            $command->localeCode,
            $command->channelCode
        ), [new DispatchAfterCurrentBusStamp()]);

        if (!$channel->isAccountVerificationRequired()) {
            $user->setEnabled(true);

            return $user;
        }

        $token = $this->tokenGenerator->generate();
        $user->setEmailVerificationToken($token);

        $this->commandBus->dispatch(new SendAccountVerificationEmail(
            $command->email,
            $command->localeCode,
            $command->channelCode
        ), [new DispatchAfterCurrentBusStamp()]);

        return $user;
    }
}
