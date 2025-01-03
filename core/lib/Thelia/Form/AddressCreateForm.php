<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thelia\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Model\Base\CountryQuery;
use Thelia\Model\CustomerTitleQuery;
use Thelia\Model\Map\CountryI18nTableMap;
use Thelia\Model\Map\CustomerTitleI18nTableMap;
use Thelia\Model\Map\StateI18nTableMap;
use Thelia\Model\StateQuery;

/**
 * Class AddressCreateForm.
 *
 * @author Manuel Raynaud <manu@raynaud.io>
 */
class AddressCreateForm extends FirewallForm
{
    use AddressCountryValidationTrait;

    /**
     * in this function you add all the fields you need for your Form.
     * Form this you have to call add method on $this->formBuilder attribute :.
     *
     * $this->formBuilder->add("name", TextType::class)
     *   ->add("email", EmailType::class, array(
     *           "attr" => array(
     *               "class" => "field"
     *           ),
     *           "label" => "email",
     *           "constraints" => array(
     *               new \Symfony\Component\Validator\Constraints\NotBlank()
     *           )
     *       )
     *   )
     *   ->add('age', IntegerType::class);
     *
     * @return null
     */
    protected function buildForm()
    {

        $this->formBuilder
            ->add('label', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => Translator::getInstance()->trans('Address label'),
                'label_attr' => [
                    'for' => 'address_label',
                ],
            ])
            ->add('title', ChoiceType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'choices' => $this->getCustomerTitleChoices(),
                'label' => Translator::getInstance()->trans('Title'),
                'attr' => [
                    'placeholder' => Translator::getInstance()->trans('Title')
                ],
                'label_attr' => [
                    'for' => 'title',
                ],
            ])
            ->add('firstname', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => Translator::getInstance()->trans('First Name'),
                'label_attr' => [
                    'for' => 'firstname',
                ],
            ])
            ->add('lastname', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => Translator::getInstance()->trans('Last Name'),
                'label_attr' => [
                    'for' => 'lastname',
                ],
            ])
            ->add('company', TextType::class, [
                'label' => Translator::getInstance()->trans('Company Name'),
                'label_attr' => [
                    'for' => 'company',
                ],
                'required' => false,
            ])
            ->add('address1', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'label' => Translator::getInstance()->trans('Street Address'),
                'label_attr' => [
                    'for' => 'address1',
                ],
            ])
            ->add('address2', TextType::class, [
                'label' => Translator::getInstance()->trans('Address Line 2'),
                'label_attr' => [
                    'for' => 'address2',
                ],
                'required' => false,
            ])
            ->add('address3', TextType::class, [
                'label' => Translator::getInstance()->trans('Address Line 3'),
                'label_attr' => [
                    'for' => 'address3',
                ],
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                    new Constraints\Callback(
                        [$this, 'verifyCity']
                    ),
                ],
                'label' => Translator::getInstance()->trans('City'),
                'label_attr' => [
                    'for' => 'city',
                ],
            ])
            ->add('zipcode', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                    new Constraints\Callback(
                        [$this, 'verifyZipCode']
                    ),
                ],
                'label' => Translator::getInstance()->trans('Zip code'),
                'label_attr' => [
                    'for' => 'zipcode',
                ],
            ])
            ->add('country', ChoiceType::class, [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
                'choices' => $this->getCountryChoices(),
                'label' => Translator::getInstance()->trans('Country'),
                'attr' => [
                    'placeholder' => Translator::getInstance()->trans('Country')
                ],
                'label_attr' => [
                    'for' => 'country',
                ],
            ])
            ->add('state', ChoiceType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Callback(
                        [$this, 'verifyState']
                    ),
                ],
                'choices' => $this->getStatesChoices(),
                'label' => Translator::getInstance()->trans('State *'),
                'label_attr' => [
                    'for' => 'state',
                ],
            ])
            // Phone
            ->add('phone', TelType::class, [
                'label' => Translator::getInstance()->trans('Phone'),
                'label_attr' => [
                    'for' => 'phone',
                ],
                'required' => false,
            ])
            ->add('cellphone', TelType::class, [
                'label' => Translator::getInstance()->trans('Cellphone'),
                'label_attr' => [
                    'for' => 'cellphone',
                ],
            ])
            // Default address
            ->add('is_default', CheckboxType::class, [
                'label' => Translator::getInstance()->trans('Make this address as my primary address'),
                'label_attr' => [
                    'for' => 'default_address',
                ],
                'required' => false,
            ])
        ;
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public static function getName()
    {
        return 'thelia_address_creation';
    }


    protected function getDefaultCountry()
    {
        $country = CountryQuery::create()->filterByByDefault(true)->findOne();

        return $country;
    }

    protected function getCustomerTitleChoices(): array
    {

        $customertitles = CustomerTitleQuery::create()
            ->useCustomerTitleI18nQuery()
            ->filterByLocale($this->getLocale())
            ->withColumn(CustomerTitleI18nTableMap::COL_LONG, 'long_title')
            ->endUse()
            ->find();

        $titleChoices = [];

        foreach ($customertitles as $title) {
            $titleChoices[$title->getVirtualColumn('long_title')] = $title->getId();
        }

        return $titleChoices;
    }

    protected function getCountryChoices(): array
    {

        $coutries = CountryQuery::create()
            ->useCountryI18nQuery()
            ->filterByLocale($this->getLocale())
            ->withColumn(CountryI18nTableMap::COL_TITLE, 'title')
            ->endUse()
            ->find();

        $countryChoices = [];

        foreach ($coutries as $country) {
            $countryChoices[$country->getVirtualColumn('title')] = $country->getId();
        }

        return $countryChoices;
    }

    protected function getLocale()
    {
        $session = $this->request?->getSession();
        return $session?->getLang()->getLocale();
    }

    protected function getStatesChoices(): array
    {

        $states = StateQuery::create()
            ->useStateI18nQuery()
            ->filterByLocale($this->getLocale())
            ->withColumn(StateI18nTableMap::COL_TITLE, 'title')
            ->endUse()
            ->find();

        $statesChoices = [];
        foreach ($states as $state) {
            $statesChoices[$state->getVirtualColumn('title')] = $state->getId();
        }
        return $statesChoices;
    }
}
