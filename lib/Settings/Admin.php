<?php
declare(strict_types=1);

namespace OCA\Virwork_API\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	    /** @var Collector */
        private $collector;

        /** @var IConfig */
        private $config;

        /** @var IL10N */
        private $l;

        /** @var IDateTimeFormatter */
        private $dateTimeFormatter;

        /** @var IJobList */
        private $jobList;

           /**
         * Admin constructor.
         *
         * @param Collector $collector
         * @param IConfig $config
         * @param IL10N $l
         * @param IDateTimeFormatter $dateTimeFormatter
         * @param IJobList $jobList
         */
        public function __construct(Collector $collector,
                                                                IConfig $config,
                                                                IL10N $l,
                                                                IDateTimeFormatter $dateTimeFormatter,
                                                                IJobList $jobList
        ) {
                $this->collector = $collector;
                $this->config = $config;
                $this->l = $l;
                $this->dateTimeFormatter = $dateTimeFormatter;
                $this->jobList = $jobList;
        }

       /**
         * @return TemplateResponse
         */
	public function getForm(): TemplateResponse {
		return new TemplateResponse(
			'virwork_api',
			'admin',
			[],
			''
		);
	}

	public function getSection(): string {
		return 'security';
	}

	public function getPriority(): int {
		return 0;
	}
}
