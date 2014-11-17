<?php

/**
 * ExoOnLine
 * Copyright or © or Copr. Université Jean Monnet (France), 2012
 * dsi.dev@univ-st-etienne.fr
 *
 * This software is a computer program whose purpose is to [describe
 * functionalities and technical features of your software].
 *
 * This software is governed by the CeCILL license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace UJM\ExoBundle\Repository;

use Doctrine\ORM\EntityRepository;
use UJM\ExoBundle\Entity\Interaction;
use UJM\ExoBundle\Entity\Paper;

/**
 * ResponseRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ResponseRepository extends EntityRepository
{
    /**
     * Allow to know if exists already a response for a question of a student's paper
     *
     */
    public function getAlreadyResponded(Paper $paper, Interaction $interaction)
    {
    	$dql = "SELECT r
    			FROM UJM\ExoBundle\Entity\Response r
    			WHERE r.paper = :paper
    				AND r.interaction = :interaction";
    	$query = $this->_em->createQuery($dql);
    	$query->setParameters(array(
    			"paper"			=> $paper,
    			"interaction"	=> $interaction
    	));

        return $query->getOneOrNullResult();
    }

    /**
     * Send the reponses for a paper
     *
     */
    public function getPaperResponses($uid, $paperID)
    {
        $qb = $this->createQueryBuilder('r');
        $qb->join('r.paper', 'p')
            ->join('p.user', 'u')
            ->where($qb->expr()->in('p.id', $paperID))
            ->andWhere($qb->expr()->in('u.id', $uid));

        return $qb->getQuery()->getResult();
    }

    /**
     * Send the reponses for an exercise and an interaction with count
     *
     */
    public function getExerciseInterResponsesWithCount($exoId, $interId)
    {
        $dql = 'SELECT r.mark, count(r.mark) as nb
            FROM UJM\ExoBundle\Entity\Response r, UJM\ExoBundle\Entity\Interaction i, UJM\ExoBundle\Entity\Question q, UJM\ExoBundle\Entity\Paper p
            WHERE r.interaction=i.id AND i.question=q.id AND r.paper=p.id AND p.exercise='.$exoId.' AND r.interaction ='.$interId.' AND r.response != \'\' GROUP BY r.mark';

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    /**
     * Send the reponses for an exercise and an interaction
     *
     */
    public function getExerciseInterResponses($exoId, $interId)
    {
        $dql = 'SELECT r.mark
            FROM UJM\ExoBundle\Entity\Response r, UJM\ExoBundle\Entity\Interaction i, UJM\ExoBundle\Entity\Question q, UJM\ExoBundle\Entity\Paper p
            WHERE r.interaction=i.id AND i.question=q.id AND r.paper=p.id AND p.exercise='.$exoId.' AND r.interaction ='.$interId.' ORDER BY p.id';

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }
    
    public function countByInteractionIn(array $interactions) {
    	$dql = "SELECT COUNT(DISTINCT r) AS nbResponses,
    				i.id AS interactionId
    			FROM UJM\ExoBundle\Entity\Response r
    			JOIN r.interaction i
    				WITH i IN (:interactions)
    			GROUP BY i.id";
    	
    	$query = $this->_em->createQuery($dql);
    	$query->setParameter("interactions", $interactions);
    	
    	return $query->getResult();
    }
}