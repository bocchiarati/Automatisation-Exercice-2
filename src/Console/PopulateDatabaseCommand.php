<?php

namespace App\Console;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use Faker\Factory;
use Illuminate\Support\Facades\Schema;
use Slim\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PopulateDatabaseCommand extends Command
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('db:populate');
        $this->setDescription('Populate database');
    }

    protected function execute(InputInterface $input, OutputInterface $output ): int
    {
        $output->writeln('Populate database...');

        /** @var \Illuminate\Database\Capsule\Manager $db */
        $db = $this->app->getContainer()->get('db');

        $db->getConnection()->statement("SET FOREIGN_KEY_CHECKS=0");
        $db->getConnection()->statement("TRUNCATE `employees`");
        $db->getConnection()->statement("TRUNCATE `offices`");
        $db->getConnection()->statement("TRUNCATE `companies`");
        $db->getConnection()->statement("SET FOREIGN_KEY_CHECKS=1");

        $faker = Factory::create("fr_FR");
        for($company = 1; $company < 4; $company++) {
            $company_data = $this->createRandomCompany($db, $faker);

            for($officies = 1; $officies < 4; $officies++) {
                $officies_id = $this->createRandomOffices($db, $faker, $company_data);

                for($employees = 1; $employees < 8; $employees++) {
                    $this->createRandomEmployees($db, $faker, [
                        "id" => $officies_id,
                        "company_slug" => $company_data["slug"],
                    ]);
                }
            }
        }

        $db->getConnection()->statement("update companies set head_office_id = 1 where id = 1;");
        $db->getConnection()->statement("update companies set head_office_id = 3 where id = 2;");

        $output->writeln('Database created successfully!');
        return 0;
    }

    private function createRandomCompany($db, $faker){
        $id = $faker->unique()->numberBetween(1, 100);
        $name = $faker->company;
        $phone = $faker->phoneNumber;
        $email = $faker->freeEmailDomain;
        $email = strtolower(str_replace(' ', '.', $name)) . '@' . $email;
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $link  = "https://www." . $slug . ".com";
        $image_link  = $faker->imageUrl;



        $db->getConnection()->statement("INSERT INTO `companies` VALUES
            ($id,'$name', '$phone', '$email' ,'$link','$image_link', now(), now(), null)
        ");

        return ["slug" => $slug, "id" => $id];
    }

    private function createRandomOffices($db, $faker, $company_data) {
        $id = $faker->unique->numberBetween(1, 100);
        $city = $faker->city;
        $name = "Bureau de ". $city;
        $address = $faker->streetAddress;
        $postal_code = $faker->postcode;
        $country  = $faker->country;
        $email = $city . '@' . $company_data["slug"] . ".com";
        $company_id = $company_data["id"];

        $db->getConnection()->statement("INSERT INTO `offices` VALUES
            ($id,'$name','$address','$city','$postal_code','$country','$email',NULL,$company_id, now(), now())");

        $db->getConnection()->statement("update companies set head_office_id = $id where id = $company_id;");

        return $id;
    }

    private function createRandomEmployees($db, $faker, $office_data)
    {
        $id = $faker->unique->numberBetween(1, 100);
        $first_name = $faker->firstName;
        $last_name = $faker->lastName;
        $office_id = $office_data["id"];
        $email = $first_name . '@' . $office_data["company_slug"] . ".com";
        $poste = str_replace("'", "''", $faker->jobTitle);

        $db->getConnection()->statement("INSERT INTO `employees` VALUES
            ($id,'$first_name','$last_name',$office_id,'$email',NULL,'$poste', now(), now())");
    }
}
