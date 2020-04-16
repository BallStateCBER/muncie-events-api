<?php
namespace App\Model\Table;

use App\Model\Entity\Event;
use App\Model\Entity\Tag;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Events Model
 *
 * @property UsersTable|BelongsTo $Users
 * @property CategoriesTable|BelongsTo $Categories
 * @property EventSeriesTable|BelongsTo $EventSeries
 * @property ImagesTable|BelongsToMany $Images
 * @property TagsTable|BelongsToMany $Tags
 *
 * @method Event get($primaryKey, $options = [])
 * @method Event newEntity($data = null, array $options = [])
 * @method Event[] newEntities(array $data, array $options = [])
 * @method Event|bool save(EntityInterface $entity, $options = [])
 * @method Event patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method Event[] patchEntities($entities, array $data, array $options = [])
 * @method Event findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin TimestampBehavior
 */
class EventsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('events');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Search.Search');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);
        $this->belongsTo('Categories', [
            'foreignKey' => 'category_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('EventSeries', [
            'foreignKey' => 'series_id',
        ]);
        $this->belongsToMany('Images', [
            'foreignKey' => 'event_id',
            'targetForeignKey' => 'image_id',
            'joinTable' => 'events_images',
            'saveStrategy' => 'replace',
        ]);
        $this->belongsToMany('Tags', [
            'foreignKey' => 'event_id',
            'targetForeignKey' => 'tag_id',
            'joinTable' => 'events_tags',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id');

        $validator
            ->integer('category_id')
            ->requirePresence('category_id', 'create');

        $validator
            ->scalar('title')
            ->requirePresence('title', 'create')
            ->allowEmptyString('title', 'Event title cannot be empty', false);

        $validator
            ->scalar('description')
            ->requirePresence('description', 'create')
            ->allowEmptyString('description', 'Event description cannot be empty', false);

        $validator
            ->scalar('location')
            ->requirePresence('location', 'create')
            ->allowEmptyString('location', 'Event location cannot be empty', false);

        $validator
            ->scalar('location_details')
            ->allowEmptyString('location_details');

        $validator
            ->scalar('address')
            ->allowEmptyString('address');

        $validator
            ->date('date')
            ->requirePresence('date', 'create')
            ->allowEmptyDate('date', 'Event date must be specified', false);

        $validator
            ->time('time_start')
            ->requirePresence('time_start', 'create')
            ->allowEmptyTime('time_start', 'Event start time must be specified', false);

        $validator
            ->time('time_end')
            ->allowEmptyTime('time_end');

        $validator
            ->scalar('age_restriction')
            ->allowEmptyString('age_restriction');

        $validator
            ->scalar('cost')
            ->allowEmptyString('cost');

        $validator
            ->scalar('source')
            ->allowEmptyString('source');

        $validator
            ->boolean('published')
            ->requirePresence('published', 'create');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param RulesChecker $rules The rules object to be modified.
     * @return RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));
        $rules->add($rules->existsIn(['approved_by'], 'Users'));
        $rules->add($rules->existsIn(['category_id'], 'Categories'));
        $rules->add($rules->existsIn(['series_id'], 'EventSeries'));

        return $rules;
    }

    /**
     * Applies default parameters to the events query for an API call
     *
     * @param Query $query Query
     * @return Query
     */
    public function findForApi(Query $query)
    {
        $query
            ->where(['Events.published' => true])
            ->contain([
                'Categories',
                'EventSeries',
                'Images',
                'Tags',
                'Users',
            ]);

        return $query;
    }

    /**
     * Limits the query to events on or after the specified date
     *
     * @param Query $query Query
     * @param array $options Array of options, with 'date' expected
     * @return $this|Query
     * @throws InternalErrorException
     * @throws BadRequestException
     */
    public function findStartingOn(Query $query, array $options)
    {
        if (!array_key_exists('date', $options)) {
            throw new InternalErrorException("\$options['date'] unspecified");
        }

        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $options['date'])) {
            throw new BadRequestException('Dates must be in the format YYYY-MM-DD');
        }

        return $query
            ->where([
                function (QueryExpression $exp) use ($options) {
                    return $exp->gte('date', $options['date']);
                },
            ]);
    }

    /**
     * Limits the query to events before or on the specified date
     *
     * Allows 'date' to be null, which leaves the query unaffected
     *
     * @param Query $query Query
     * @param array $options Array of options, with 'date' expected
     * @return $this|Query
     * @throws InternalErrorException
     * @throws BadRequestException
     */
    public function findEndingOn(Query $query, array $options)
    {
        if (!array_key_exists('date', $options)) {
            throw new InternalErrorException("\$options['date'] unspecified");
        }

        if (!$options['date']) {
            return $query;
        }

        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $options['date'])) {
            throw new BadRequestException('Dates must be in the format YYYY-MM-DD');
        }

        return $query
            ->where([
                function (QueryExpression $exp) use ($options) {
                    return $exp->lte('date', $options['date']);
                },
            ]);
    }

    /**
     * Limits the query to events with the supplied tags
     *
     * Allows 'tags' to be null, which leaves the query unaffected
     *
     * @param Query $query Query
     * @param array $options Array of options, with 'tags' expected
     * @return $this|Query
     * @throws InternalErrorException
     * @throws BadRequestException
     */
    public function findTagged(Query $query, array $options)
    {
        if (!array_key_exists('tags', $options)) {
            throw new InternalErrorException("\$options['tags'] unspecified");
        }

        $tags = $options['tags'];
        if (empty($tags)) {
            return $query;
        }

        if (!is_array($tags)) {
            throw new BadRequestException('Tags must be provided as an array');
        }

        $tags = array_map('mb_strtolower', $tags);
        $conditions = [];
        foreach ($tags as $tag) {
            $conditions[] = ['Tags.name' => $tag];
        }

        return $query
            ->leftJoinWith('Tags')
            ->where(['OR' => $conditions]);
    }

    /**
     * Limits the query to events in the specified category
     *
     * @param Query $query Query
     * @param array $options Array of options, with 'categoryId' expected
     * @return $this|Query
     * @throws InternalErrorException
     * @throws BadRequestException
     */
    public function findInCategory(Query $query, array $options)
    {
        if (!array_key_exists('categoryId', $options)) {
            throw new InternalErrorException("\$options['categoryId'] unspecified");
        }

        $categoryId = $options['categoryId'];
        if (empty($categoryId)) {
            return $query;
        }

        return $query->where(['category_id' => $categoryId]);
    }

    /**
     * Limits the query to events on or after today's date
     *
     * @param Query $query Query
     * @return $this|Query
     * @throws InternalErrorException
     * @throws BadRequestException
     */
    public function findFuture(Query $query)
    {
        return $query
            ->where([
                function (QueryExpression $exp) {
                    return $exp->gte('date', date('Y-m-d'));
                },
            ]);
    }

    /**
     * Limits the query to events before today's date
     *
     * @param Query $query Query
     * @return $this|Query
     * @throws InternalErrorException
     * @throws BadRequestException
     */
    public function findPast(Query $query)
    {
        return $query
            ->where([
                function (QueryExpression $exp) {
                    return $exp->lt('date', date('Y-m-d'));
                },
            ]);
    }

    /**
     * Returns the count of upcoming events in the specified category
     *
     * @param int $categoryId Category ID
     * @return int
     */
    public function getCategoryUpcomingEventCount($categoryId)
    {
        return $this
            ->find('future')
            ->find('inCategory', ['categoryId' => $categoryId])
            ->count();
    }

    /**
     * Returns an alphabetized array of tags associated with upcoming published events,
     * plus the count of how many events each is associated with
     *
     * @return Tag[]
     */
    public function getUpcomingEventTags()
    {
        $events = $this->find('future')
            ->select(['id'])
            ->where(['published' => true])
            ->contain([
                'Tags' => function (Query $query) {
                    return $query->select(['id', 'name']);
                },
            ])
            ->all();

        $tags = [];
        foreach ($events as $event) {
            foreach ($event->tags as $tag) {
                if (isset($tags[$tag->name])) {
                    $tags[$tag->name]->count++;
                    continue;
                }
                $tag->count = 1;
                $tags[$tag->name] = $tag;
            }
        }

        ksort($tags);

        return $tags;
    }
}
