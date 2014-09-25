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

/**
 * PaperRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PaperRepository extends EntityRepository
{
    /**
     * Returns a student's Paper which is not finished
     *
     */
    public function getPaper($userID, $exerciseID)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->join('p.user', 'u')
            ->join('p.exercise', 'e')
            ->where($qb->expr()->in('u.id', $userID))
            ->andWhere($qb->expr()->in('e.id', $exerciseID))
            ->andWhere('p.end IS NULL');

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the user's papers for an exercise
     *
     */
    public function getExerciseUserPapers($userID, $exerciseID, $orderBy = "id", $orderDirection = "ASC")
    {
        $qb = $this->createQueryBuilder('p');
        $qb->join('p.user', 'u')
            ->join('p.exercise', 'e')
            ->where($qb->expr()->in('u.id', $userID))
            ->andWhere($qb->expr()->in('e.id', $exerciseID))
            ->orderBy('p.'.$orderBy, $orderDirection);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns all papers for an exercise
     *
     */
    public function getExerciseAllPapers($exerciseID)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->join('p.exercise', 'e')
            ->join('p.user', 'u')
            ->where($qb->expr()->in('e.id', $exerciseID))
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->addOrderBy('p.id', 'ASC');

        return $qb->getQuery()->getResult();
    }
    
    /**
     * Returns all papers for an exercise for CSV export
     *
     */
    public function getExerciseAllPapersIterator($exerciseID)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->join('p.exercise', 'e')
            ->join('p.user', 'u')
            ->where($qb->expr()->in('e.id', $exerciseID))
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->addOrderBy('p.id', 'ASC');

        return $qb->getQuery()->iterate();
    }

    public function getPaperUser($userID)
    {
        $dql = 'SELECT p FROM UJM\ExoBundle\Entity\Paper p
                WHERE p.user = '.$userID.'
        ';

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }
}