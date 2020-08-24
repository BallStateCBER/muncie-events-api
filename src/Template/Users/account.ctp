<?php
/**
 * @var \App\Model\Entity\User $user
 * @var \App\View\AppView $this
 * @var bool $hasSubscription
 * @var string $pageTitle
 */
?>
<h1 class="page_title">
    <?= $pageTitle ?>
</h1>

<div id="my_account">
    <ul>
        <li>
            <?= $this->Html->link(
                'Change Password',
                [
                    'controller' => 'Users',
                    'action' => 'changePass',
                ]
            ) ?>
        </li>
        <li>
            <?= $this->Html->link(
                $hasSubscription ? 'Update Mailing List Settings' : 'Join Mailing List',
                [
                    'controller' => 'MailingList',
                    'action' => 'index',
                ]
            ) ?>
        </li>
    </ul>

    <?= $this->Form->create($user) ?>
    <?= $this->Form->control('name', [
        'after' => '<div class="text-muted">Your first and last actual name, please</div>',
    ]) ?>
    <?= $this->Form->control('email') ?>
    <?= $this->Form->submit('Update', ['class' => 'btn btn-primary']) ?>
</div>
