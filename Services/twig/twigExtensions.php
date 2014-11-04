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

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace UJM\ExoBundle\Services\twig;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\Request;

use UJM\ExoBundle\Services\classes\exerciseServices;

class twigExtensions extends \Twig_Extension
{
    protected $doctrine;
    protected $exerciseSer;

    public function __construct(Registry $doctrine, exerciseServices $exerciseSer)
    {
        $this->doctrine  = $doctrine;
        $this->exerciseSer = $exerciseSer;
    }

    public function getName()
    {
        return "twigExtensions";
    }

    public function getFunctions()
    {

        return array(
            'regexTwig'            => new \Twig_Function_Method($this, 'regexTwig'),
            'getInterTwig'         => new \Twig_Function_Method($this, 'getInterTwig'),
            'getCoordsGraphTwig'   => new \Twig_Function_Method($this, 'getCoordsGraphTwig'),
            'roundUpOrDown'        => new \Twig_Function_Method($this, 'roundUpOrDown'),
        );

    }

    public function regexTwig($pattern, $str)
    {
        //return int
        return preg_match((string) $pattern, (string) $str);
    }

    public function getInterTwig($interId, $typeInter)
    {
        //$em = $this->doctrine->getManager();

        switch ($typeInter)
        {
            case "InteractionQCM":
                $interQCM = $this->doctrine
                                 ->getManager()
                                 ->getRepository('UJMExoBundle:InteractionQCM')
                                 ->getInteractionQCM($interId);
                $inter['question'] = $interQCM[0];
                $inter['maxScore'] = $this->getQCMScoreMax($interQCM[0]);
            break;

            case "InteractionGraphic":
                $interG = $this->doctrine
                               ->getManager()
                               ->getRepository('UJMExoBundle:InteractionGraphic')
                               ->getInteractionGraphic($interId);
                $inter['question'] = $interG[0];
                $inter['maxScore'] = $this->getGraphixScoreMax($interG[0]);
            break;

            case "InteractionHole":
                $interHole = $this->doctrine
                                  ->getManager()
                                  ->getRepository('UJMExoBundle:InteractionHole')
                                  ->getInteractionHole($interId);
                $inter['question'] = $interHole[0];
                $inter['maxScore'] = $this->getHoleScoreMax($interHole[0]);
            break;

            case "InteractionOpen":
                $interOpen = $this->doctrine
                               ->getManager()
                               ->getRepository('UJMExoBundle:InteractionOpen')
                               ->getInteractionOpen($interId);
                $inter['question'] = $interOpen[0];
                $inter['maxScore'] = $this->getOpenScoreMax($interOpen[0]);
            break;
        }

        return $inter;
    }

    public function getCoordsGraphTwig($interGraphId)
    {
        $coords = $this->doctrine
                       ->getManager()
                       ->getRepository('UJMExoBundle:Coords')
                       ->findBy(array('interactionGraphic' => $interGraphId));

        return $coords;
    }

    public function roundUpOrDown($markToBeAdjusted)
    {
        return $this->exerciseSer->roundUpDown($markToBeAdjusted);
    }

    private function getQCMScoreMax($interQCM)
    {
        return $this->exerciseSer->qcmMaxScore($interQCM);
    }

    private function getOpenScoreMax($interOpen)
    {
        return $this->exerciseSer->openMaxScore($interOpen);
    }

    private function getHoleScoreMax($interHole)
    {
    	return $this->exerciseSer->holeMaxScore($interHole);
    }

    private function getGraphixScoreMax($interGraphic)
    {
    	return $this->exerciseSer->graphicMaxScore($interGraphic);
    }
}