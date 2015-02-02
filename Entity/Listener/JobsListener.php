<?php

namespace JMS\JobQueueBundle\Entity\Listener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use JMS\JobQueueBundle\Entity\Job;
use JMS\JobQueueBundle\Entity\JobTag;


class JobsListener
{
	public function prePersist(LifecycleEventArgs $args)
	{
		$entity = $args->getEntity();
		if ( ! $entity instanceof Job) {
			return;
		}

		$names = array();
		$collection = $entity->getTags();
		if ($collection->count()) {
			foreach($entity->getTags() as $key => $tag) {
				if (!$tag->getId()) {
					unset($collection[$key]);
					$names[$tag->getName()] = $tag->getName();
				}
			}

			$qb = $args->getEntityManager()->getRepository('JMSJobQueueBundle:JobTag')->createQueryBuilder('jt');
			$qb->where($qb->expr()->in('jt.name', array_values($names)));

			foreach($qb->getQuery()->getResult() as $tag) {
				/** @var $tag JobTag */
				unset($names[$tag->getName()]);
				$collection->add($tag);
			}

			foreach ($args->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions() as $object) {
				if ($object instanceof JobTag && in_array($object->getName(), $names)) {
					unset($names[$object->getName()]);
					$collection->add($object);
				}
			}

			foreach($names as $name) {
				$collection->add((new JobTag)->setName($name));
			}
		}
	}
}