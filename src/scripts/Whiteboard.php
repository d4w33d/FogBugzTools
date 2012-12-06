<?php

class WhiteboardCommand extends Command
{

    protected $fgz;

    public function __construct()
    {
        parent::__construct();
        $this->fgz = new FogBugzClient(Config::FOGBUGZ_ACCOUNT_EMAIL, Config::FOGBUGZ_ACCOUNT_PASSWORD);
    }

    public function executeGenerate($args, $opts)
    {
        $fixForID = isset($opts['milestone']) ? $opts['milestone'] : $this->selectFixFor();
        $fixFor = $this->fgz->_getFixFor($fixForID);
        $query = array(
            'q' => 'milestone:"' . $fixFor->sFixFor . '" parent:"0"',
            'cols' => FogBugzClient::COMMON_COLUMNS
        );

        $printableIndex = new WhiteboardPrintableIndex();
        $printableIndex->setFixFor($fixFor);

        foreach ($this->fgz->exec('search', $query)->cases->case as $case)
        {
            if ((string) $case->fOpen !== 'true')
            {
                continue;
            }

            $printable = new WhiteboardPrintableSheet();
            $printable->setFixFor($fixFor);
            $printable->setMainCase($case);
            $printable->addCase($case);

            $childrenNum = 0;
            $this->writeln((string) $case->ixBug . ': ' . (string) $case->sTitle);
            if ($childrenIDs = trim((string) $case->ixBugChildren))
            {
                foreach ($this->fgz->_getCases(explode(',', $childrenIDs))->cases->case as $child)
                {
                    if ((string) $child->fOpen !== 'true')
                    {
                        continue;
                    }
                    $printable->addCase($child);
                    $childrenNum++;
                    $this->writeln('   ' . (string) $child->ixBug . ': ' . (string) $child->sTitle);
                }
            }

            $printableIndex->addMainCase($case, $childrenNum);
            $fileName = $printable->save();
        }

        $fileName = $printableIndex->save();
        $this->writeln('file://' . $fileName);
    }

    protected function selectFixFor()
    {
        $this->writeln('Milestones:');
        foreach ($this->fgz->exec('listFixFors')->fixfors->fixfor as $fixfor)
        {
            $name = (string) $fixfor->sFixFor;
            if (!preg_match('/^SPRINT [0-9]+/', $name))
            {
                continue;
            }
            $this->writeln('  * [' . (int) $fixfor->ixFixFor . '] ' . $name);
        }
        $fixForID = trim($this->prompt('Enter the milestone ID: '));
        if (!$fixForID)
        {
            $this->abort();
        }
        return $fixForID;
    }

}

class WhiteboardPrintableSheet
{

    private $fixFor;
    private $mainCase;
    private $cases;

    public function __construct()
    {
        $this->cases = array();
    }

    public function setFixFor($fixFor)
    {
        $this->fixFor = $fixFor;
    }

    public function setMainCase($mainCase)
    {
        $this->mainCase = $mainCase;
    }

    public function addCase($case)
    {
        $this->cases[] = $case;
    }

    public function fetch()
    {
        $fixFor = $this->fixFor;
        $mainCase = $this->mainCase;
        $sheets = array();

        $itemsPerSheet = 6;
        $itemsTotal = count($this->cases);
        $pages = ceil($itemsTotal / $itemsPerSheet);

        $caseNum = 0;
        for ($i = 0; $i < $pages; $i++)
        {
            $sheet = new stdClass();
            $sheet->num = $i + 1;
            $sheet->rows = array();

            for ($j = 0; $j < $itemsPerSheet; $j++)
            {
                if ($j % 2 !== 1)
                {
                    $row = new stdClass();
                    $row->cases = array();
                    $sheet->rows[] = $row;
                }

                $row->cases[] = $this->cases[$caseNum];

                $caseNum++;
                if ($caseNum > $itemsTotal - 1)
                {
                    break;
                }
            }

            $sheets[] = $sheet;
        }

        ob_start();
        require ROOT_DIR . DS . 'data' . DS . 'whiteboard' . DS . 'template' . DS . 'sheet.phtml';
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    public function save()
    {
        $dir = ROOT_DIR . DS . 'data' . DS . 'whiteboard' . DS . 'generated' . DS
            . 'milestone-' . (string) $this->fixFor->ixFixFor;
        if (!is_dir($dir))
        {
            mkdir($dir);
        }
        $fileName = $dir . DS . 'case-' . (string) $this->mainCase->ixBug . '.html';

        $output = $this->fetch();
        file_put_contents($fileName, $output);
        return $fileName;
    }

}

class WhiteboardPrintableIndex
{

    private $fixFor;
    private $mainCases;

    public function __construct()
    {
        $this->cases = array();
    }

    public function setFixFor($fixFor)
    {
        $this->fixFor = $fixFor;
    }

    public function addMainCase($mainCase, $childrenNum)
    {
        $case = new stdClass();
        $case->case = $mainCase;
        $case->childrenNum = $childrenNum;
        $this->mainCases[] = $case;
    }

    public function fetch()
    {
        $fixFor = $this->fixFor;
        $mainCases = $this->mainCases;

        ob_start();
        require ROOT_DIR . DS . 'data' . DS . 'whiteboard' . DS . 'template' . DS . 'index.phtml';
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    public function save()
    {
        $dir = ROOT_DIR . DS . 'data' . DS . 'whiteboard' . DS . 'generated' . DS
            . 'milestone-' . (string) $this->fixFor->ixFixFor;
        if (!is_dir($dir))
        {
            mkdir($dir);
        }
        $fileName = $dir . DS . 'index.html';

        $output = $this->fetch();
        file_put_contents($fileName, $output);
        return $fileName;
    }

}
