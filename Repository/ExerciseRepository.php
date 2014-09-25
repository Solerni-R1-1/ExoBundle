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
use UJM\ExoBundle\Entity\Exercise;
use Claroline\CoreBundle\Entity\User;
use UJM\ExoBundle\Entity\Paper;

/**
 * ExerciseRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ExerciseRepository extends EntityRepository
{
public function getExerciseMarks($exoId, $order = '')
    {
    	$orderBy = '';
        if ($order != '') {
            $orderBy = ' ORDER BY '.$order;
        }
        $dql = 'SELECT sum(r.mark) as noteExo, p.id as paper
            FROM UJM\ExoBundle\Entity\Response r JOIN r.paper p JOIN p.exercise e
            WHERE e.id='.$exoId.' AND p.interupt=0 group by p.id'.$orderBy;

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }
    
    public function getExerciseMarksForUser(Exercise $exercise, User $user)
    {
    	$dql = 'SELECT 
    				sum(r.mark) AS noteExo
            	FROM UJM\ExoBundle\Entity\Response r 
    			JOIN r.paper p
            	WHERE p.exercise = :exercise
    			AND   p.interupt = 0
    			AND   p.user 	 = :user 
    			GROUP BY p.id';
    
    	$query = $this->_em->createQuery($dql);
    	$query->setParameters(array(
    			"exercise" 	=> $exercise,
    			"user"		=> $user
    	));
    
    	$results = $query->getResult();
    	
    	return $results;
    }
    
	public function getMaximalMarkForPaperQCM(Paper $paper) {
    	$dql = "SELECT SUM(CASE WHEN (qcm.weightResponse = 0) THEN qcm.scoreRightResponse ELSE choice.weight END) as score
					FROM UJM\ExoBundle\Entity\InteractionQCM qcm
					JOIN qcm.interaction int
					JOIN UJM\ExoBundle\Entity\Response resp WITH resp.interaction = int
					JOIN UJM\ExoBundle\Entity\Paper paper WITH paper = resp.paper
    				JOIN UJM\ExoBundle\Entity\Choice choice WITH choice.interactionQCM = qcm
					WHERE paper = :paper
    				AND choice.rightResponse = true";
    	
    	$query = $this->_em->createQuery($dql);
    	$query->setParameter("paper", $paper);
    	return $query->getSingleScalarResult();
    }
    
    public function getMaximalMarkForPaperHole(Paper $paper) {
    	$dql = "SELECT SUM(word.score) as score
    				FROM UJM\ExoBundle\Entity\WordResponse word
    				JOIN word.hole hole
					JOIN hole.interactionHole intHole
					JOIN intHole.interaction int
					JOIN UJM\ExoBundle\Entity\Response resp WITH resp.interaction = int
					JOIN resp.paper paper
					WHERE paper = :paper";
    	
    	$query = $this->_em->createQuery($dql);
    	$query->setParameter("paper", $paper);
    	return $query->getSingleScalarResult();
    }
}