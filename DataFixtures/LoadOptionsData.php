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

namespace UJM\ExoBundle\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use UJM\ExoBundle\Entity\TypeOpenQuestion;
use UJM\ExoBundle\Entity\TypeQCM;


class LoadOptionsData extends AbstractFixture
{
    private $manager;

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $valTqcm = array();
        
        $valTqcm[1] = 'Multiple response';
        $valTqcm[2] = 'Unique response';
        
        foreach ($valTqcm as $code => $val) {
            $this->newTQCM($val, $code);
        }

        $valTopen = array();
        $valTopen[1] = 'numerical';
        $valTopen[2] = 'long';
        $valTopen[3] = 'short';
        $valTopen[4] = 'oneWord';
        
        foreach ($valTopen as $code => $val) {
            $this->newTOPEN($val, $code);
        }
        
        $this->manager->flush();
    }

    private function newTQCM($val, $code)
    {
        $tqcm = new TypeQCM();
        $tqcm->setId($code);
        $tqcm->setValue($val);
        $tqcm->setCode($code);

        $this->manager->persist($tqcm);
    }
    
    private function newTOPEN($val, $code)
    {
        $topen = new TypeOpenQuestion();
        $topen->setValue($val);
        $topen->setCode($code);

        $this->manager->persist($topen);
    }

}
