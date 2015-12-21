<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Framework\EventStore\Mongo\Criteria;


/**
 * Representation of an AND operator for Mongo selection criteria.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class AndX extends MongoCriteria {

    /**
     * @var MongoCriteria
     */
    private $criteria1;
    /**
     * @var MongoCriteria
     */
    private $criteria2;

    /**
     * Returns a criterium that requires both <code>criteria1</code> and <code>criteria2</code>  to be
     * <code>true</code>.
     *
     * @param MongoCriteria $criteria1 One of the criteria that must evaluate to true
     * @param MongoCriteria $criteria2 One of the criteria that must evaluate to true
     */
    public function __construct(MongoCriteria $criteria1, MongoCriteria $criteria2)
    {
        $this->criteria1 = $criteria1;
        $this->criteria2 = $criteria2;
    }


    public function asMongoObject() {
        return [
            '$and' => [
                $this->criteria1->asMongoObject(),
                $this->criteria2->asMongoObject()
            ]
        ];
    }
}