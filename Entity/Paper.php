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

namespace UJM\ExoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UJM\ExoBundle\Entity\Paper
 *
 * @ORM\Entity(repositoryClass="UJM\ExoBundle\Repository\PaperRepository")
 * @ORM\Table(name="ujm_paper")
 */
class Paper
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer $numPaper
     *
     * @ORM\Column(name="num_paper", type="integer")
     */
    private $numPaper;

    /**
     * @var \Datetime $start
     *
     * @ORM\Column(name="start", type="datetime")
     */
    private $start;

    /**
     * @var \Datetime $end
     *
     * @ORM\Column(name="end", type="datetime", nullable=true)
     */
    private $end;

    /**
     * @var integer $ordreQuestion
     *
     * @ORM\Column(name="ordre_question", type="text", nullable=true)
     */
    private $ordreQuestion;

    /**
     * @var boolean $archive
     *
     * @ORM\Column(name="archive", type="boolean", nullable=true)
     */
    private $archive;

    /**
     * @var date $dateArchive
     *
     * @ORM\Column(name="date_archive", type="date", nullable=true)
     */
    private $dateArchive;

    /**
     * @var boolean $interupt
     *
     * @ORM\Column(name="interupt", type="boolean", nullable=true)
     */
    private $interupt;

    /**
     * @ORM\ManyToOne(targetEntity="Claroline\CoreBundle\Entity\User")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="UJM\ExoBundle\Entity\Exercise",
     * 			inversedBy="papers")
     */
    private $exercise;

    /**
     * @ORM\OneToMany(
     * 		targetEntity="UJM\ExoBundle\Entity\PaperQuestion",
     * 		mappedBy="paper")
     */
    private $paperQuestions;
    
    /**
     * @var float $mark
     * 
     * @ORM\Column(type="float", nullable=true)
     */
    private $mark;
    
    /**
     * @ORM\OneToMany(
     * 		targetEntity="UJM\ExoBundle\Entity\Response",
     * 		mappedBy="paper")
     */
    private $responses;
    

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set numPaper
     *
     * @param integer $numPaper
     */
    public function setNumPaper($numPaper)
    {
        $this->numPaper = $numPaper;
    }

    /**
     * Get numPaper
     *
     * @return integer
     */
    public function getNumPaper()
    {
        return $this->numPaper;
    }

    /**
     * Set start
     *
     * @param \Datetime $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * Get start
     *
     * @return \Datetime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param \Datetime $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * Get end
     *
     * @return \Datetime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set ordreQuestion
     *
     * @param integer $ordreQuestion
     */
    public function setOrdreQuestion($ordreQuestion)
    {
        $this->ordreQuestion = $ordreQuestion;
    }

    /**
     * Get ordreQuestion
     *
     * @return integer
     */
    public function getOrdreQuestion()
    {
        return $this->ordreQuestion;
    }

    /**
     * Set archive
     *
     * @param boolean $archive
     */
    public function setArchive($archive)
    {
        $this->archive = $archive;
    }

    /**
     * Get archive
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Set dateArchive
     *
     * @param date $dateArchive
     */
    public function setDateArchive($dateArchive)
    {
        $this->dateArchive = $dateArchive;
    }

    /**
     * Get dateArchive
     *
     * @return date
     */
    public function getDateArchive()
    {
        return $this->dateArchive;
    }

    /**
     * Set interupt
     *
     * @param boolean $interupt
     */
    public function setInterupt($interupt)
    {
        $this->interupt = $interupt;
    }

    /**
     * Get interupt
     */
    public function getInterupt()
    {
        return $this->interupt;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(\Claroline\CoreBundle\Entity\User $user)
    {
        $this->user = $user;
    }

    public function getExercise()
    {
        return $this->exercise;
    }

    public function setExercise(\UJM\ExoBundle\Entity\Exercise $exercise)
    {
        $this->exercise = $exercise;
    }
    
    public function getPaperQuestions() {
    	return $this->paperQuestions;
    }
    
    public function setPaperQuestions($paperQuestions) {
    	$this->paperQuestions = $paperQuestions;
    }
    
    public function getMark() {
    	return $this->mark;
    }
    
    public function setMark($mark) {
    	$this->mark = $mark;
    }
    
    public function getResponses() {
    	return $this->responses;
    }
}