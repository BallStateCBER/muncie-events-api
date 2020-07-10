<?php
/**
 * @var AppView $this
 * @var array $days
 * @var MailingList $subscription
 * @var ResultSet|Category[] $categories
 * @var string $pageTitle
 */

use App\Model\Entity\Category;
use App\Model\Entity\MailingList;
use App\View\AppView;
use Cake\ORM\ResultSet;
use Cake\Utility\Hash;

$formClasses = [$subscription->isNew() ? 'joining' : 'updating'];
if ($subscription->event_categories == 'all') {
    $formClasses[] = 'all-categories-preselected';
}
$preselectedCategories = $subscription->isNew() ? [] : Hash::extract($subscription->categories, '{n}.id');
$daysSelected = [];
foreach ($days as $code => $day) {
    if ($subscription->{"daily_$code"}) {
        $daysSelected[] = $code;
    }
}
if ($subscription->weekly && count($daysSelected) === 0) {
    $frequencyValue = 'weekly';
} elseif (!$subscription->weekly && count($daysSelected) === 7) {
    $frequencyValue = 'daily';
} else {
    $frequencyValue = 'custom';
}
$settingsValue = ($frequencyValue == 'weekly' && $subscription->event_categories) ? 'default' : 'custom';
if ($frequencyValue != 'custom') {
    $formClasses[] = 'frequency-options-hidden';
}
?>

<h1 class="page_title">
    <?= $pageTitle ?>
</h1>

<div id="mailing-list-form">
    <?= $this->Form->create($subscription, [
        'id' => 'MailingListForm',
        'class' => implode(' ', $formClasses),
    ]) ?>
    <?= $this->Form->control('email', [
        'class' => 'form-control',
        'label' => 'Email address',
    ]) ?>

    <?php if ($subscription->isNew()): ?>
        <?= $this->Form->control('settings',
            [
                'type' => 'radio',
                'options' => [
                    'default' => 'Default Settings',
                    'custom' => 'Customize',
                ],
                'default' => 'default',
                'class' => 'mailing-list-settings-option',
                'legend' => false,
                'label' => false,
                'value' => $settingsValue,
            ]
        ) ?>
    <?php endif; ?>

    <div id="custom_options">
        <fieldset>
            <legend>Frequency</legend>
            <?php
            $formTemplate = ['inputContainer' => '{{content}}'];
            $this->Form->setTemplates($formTemplate);
            ?>
            <?= $this->Form->radio(
                'frequency',
                [
                    [
                        'value' => 'weekly',
                        'text' => 'Weekly (Thursday, next week\'s events)',
                    ],
                    [
                        'value' => 'daily',
                        'text' => 'Daily (Every morning, today\'s events)',
                    ],
                    [
                        'value' => 'custom',
                        'text' => 'Custom',
                    ],
                ],
                [
                    'class' => 'frequency_options',
                    'value' => $frequencyValue,
                ]
            ) ?>

            <div id="custom_frequency_options">
                <?php if (isset($frequencyError)): ?>
                    <div class="error">
                        <?= $frequencyError ?>
                    </div>
                <?php endif; ?>
                <table>
                    <tr>
                        <th>
                            Weekly:
                        </th>
                        <td>
                            <?= $this->Form->control(
                                'weekly',
                                [
                                    'type' => 'checkbox',
                                    'label' => ' Thursday'
                                ]
                            ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            Daily:
                        </th>
                        <td>
                            <?php foreach ($days as $code => $day): ?>
                                <?= $this->Form->control(
                                    "daily_$code",
                                    [
                                        'type' => 'checkbox',
                                        'label' => false,
                                        'id' => "daily_$code"
                                    ]
                                ) ?>
                                <label for="daily_<?= $code ?>">
                                    <?= $day ?>
                                </label>
                                <br/>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </fieldset>

        <fieldset>
            <legend>Event Types</legend>
            <?php
            $formTemplate = ['inputContainer' => '{{content}}'];
            $this->Form->setTemplates($formTemplate);
            ?>
            <?= $this->Form->radio(
                'event_categories',
                [
                    ['value' => 'all', 'text' => 'All Events'],
                    ['value' => 'custom', 'text' => 'Custom']
                ],
                ['class' => 'category_options']
            ) ?>
            <div id="custom_event_type_options">
                <?php if (isset($categoriesError)): ?>
                    <div class="error">
                        <?= $categoriesError ?>
                    </div>
                <?php endif; ?>
                <?php foreach ($categories as $category): ?>
                    <div class="form-control mailing-options">
                        <?= $this->Form->control(
                            "selected_categories.$category->id",
                            [
                                'escape' => false,
                                'type' => 'checkbox',
                                'label' => $this->Icon->category($category->name) . ' ' . $category->name,
                                'hiddenField' => false,
                                'checked' => $subscription->isNew() || in_array($category->id, $preselectedCategories)
                            ]
                        ) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
    </div>

    <?= $this->Form->button(
        isset($subscription->id) ? 'Update Subscription' : 'Join Event Mailing List',
        ['class' => 'btn btn-primary']
    ) ?>
    <?= $this->Form->end() ?>
    <?php if (isset($subscription->id)): ?>
        <?= $this->Form->postButton(
            'Unsubscribe',
            [
                'controller' => 'MailingList',
                'action' => 'unsubscribe',
                $subscription->id,
                $subscription->hash
            ],
            [
                'class' => 'btn btn-danger',
                'confirm' => 'Are you sure that you want to unsubscribe from the mailing list?',
                'method' => 'delete'
            ]
        ) ?>
    <?php endif; ?>
</div>

<?php $this->Html->script('mailing_list.js', ['block' => true]); ?>
<?php $this->Html->scriptBlock('mailingList.init();', ['block' => true]); ?>
