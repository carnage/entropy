<?php

namespace Carnage\Entropy;

class Entropy
{
    /**
     * Password strength constants. To be perfected.
     */
    const LOW = 12;
    const MEDIUM = 15;
    const STRONG = 18;

    private $groupMembers = [
        'hasLower' => 26,
        'hasUpper' => 26,
        'hasNumber' => 10,
        'hasSymbol' => 31,
        /*
         * this group could include 65k utf8 chars, however this can't be relied upon.
         * Using 25 so not to bias the result too strongly when none ascii chars are used
         * (think russian characters, german vowels etc which would be common on sites for these countries)
         * If this turns out to be a bad choice, I may will with more character groups or local aware scores to
         * compensate.
         */
        'hasNoneAscii' => 25
    ];

    /**
     * Bad word list default value from
     *
     * @link http://splashdata.com/press/worst-passwords-of-2014.htm
     * @var array
     */
    private $badWordList = [
        '123456',
        'password',
        '12345',
        '12345678',
        'qwerty',
        '123456789',
        '1234',
        'baseball',
        'dragon',
        'football',
        '1234567',
        'monkey',
        'letmein',
        'abc123',
        '111111',
        'mustang',
        'access',
        'shadow',
        'master',
        'michael',
        'superman',
        '696969',
        '123123',
        'batman',
        'trustno1'
    ];

    private $badWordCount = 0;

    public function __construct($badWordList = null)
    {
        if ($badWordList === null) {
            $badWordList = $this->badWordList;
        }

        $this->setBadWordList($badWordList);
    }

    public function calculateScore($password)
    {
        list($badWord, $password) = $this->processBadWords($password);

        if (strlen($password) === 0) {
            //was just a bad word
            return $badWord;
        }

        $groups = $this->getGroups($password);

        $entropy = 0;
        $penalty = 1;

        foreach ($groups as $group => $has) {
            if ($has) {
                $entropy += $this->groupMembers[$group];
            }
        }

        if ($entropy === 26) {
            //letters only - probably a dictionary word; add a penalty
            $penalty = 0.5;
        }

        return log10((pow($entropy, strlen($password)) * $penalty) + $badWord);
    }

    private function getGroups($password)
    {
        $length = strlen($password);
        $groups = [
            'hasLower' => false,
            'hasUpper' => false,
            'hasNumber' => false,
            'hasSymbol' => false,
            'hasNoneAscii' => false
        ];

        $password = preg_replace('/[a-z]+/', '', $password);
        if (strlen($password) < $length) {
            $groups['hasLower'] = true;
            $length = strlen($password);
        }

        $password = preg_replace('/[A-Z]+/', '', $password);
        if (strlen($password) < $length) {
            $groups['hasUpper'] = true;
            $length = strlen($password);
        }

        $password = preg_replace('/[0-9]+/', '', $password);
        if (strlen($password) < $length) {
            $groups['hasNumber'] = true;
            $length = strlen($password);
        }

        $password = preg_replace('/[!-~]+/', '', $password);
        if (strlen($password) < $length) {
            $groups['hasSymbol'] = true;
            $length = strlen($password);
        }
        
        if ($length > 0) {
            $groups['hasNoneAscii'] = true;
        }

        return $groups;
    }

    /**
     * If a password contains a bad word, the entropy score for that word is set equal to the length of the badword list
     *
     * Filters any bad words from the password so we can calculate the entropy of the rest.
     *
     * for example: password1g84Ko0rJ# is not a bad password
     *
     * @param $password
     * @return int
     */
    private function processBadWords($password)
    {
        $badWordScore = 0;

        foreach ($this->badWordList as $badWord) {
            if (stripos($password, $badWord) !== false) {
                $badWordScore += $this->badWordCount;
                //remove it so we don't add entropy for pass + password
                $password = str_ireplace($badWord, '', $password);
            }
        }

        return [$badWordScore, $password];
    }

    /**
     * Ensures list is sorted and sets the count cache
     * @param $list
     */
    private function setBadWordList($list)
    {
        $callback = function ($a, $b) {
            $al = strlen($a);
            $bl = strlen($b);
            if ($al === $bl) {
                return 0;
            }
            return ($al < $bl) ? 1 : -1;
        };
        uasort($list, $callback);

        $this->badWordList = $list;
        $this->badWordCount = count($list);
    }
}
