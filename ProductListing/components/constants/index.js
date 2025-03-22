import TopCategoriesWidget from "../inlineWidgets/TopCategoriesWidget";
import InformativeWidget from "../inlineWidgets/informativeWidget";
import FitnessWidget from "../inlineWidgets/FitnessWidget";
import SkinCareWidget from "../inlineWidgets/SkinCareWidget";
import HealthBlogsWidget from "../inlineWidgets/HealthBlogsWidget";

export const productInfo = {
    "id": "12",
    "name": "Pure Nutrition Detox Liver Milk Thistle",
    "sku": "HSIB49D6",
    "price": "999.000000",
    "slug": "pure-nutrition-detox-liver-milk-thistle",
    "image": "/p/n/pnaldl0222-1_axfkoqlrmqgmmjil.jpg",
    "media_gallery": [
        {
            "file": "/p/n/pnaldl0222-1_axfkoqlrmqgmmjil.jpg",
            "media_type": "image",
            "entity_id": "12",
            "label": null,
            "position": "1",
            "disabled": "0",
            "types": [
                "image",
                "small_image",
                "thumbnail"
            ],
            "id": "23"
        }
    ],
    "ratings": [],
    "visibility": "4",
    "stock_info": {
        "qty": 998,
        "min_qty": 0,
        "min_sale_qty": 1,
        "max_sale_qty": 10000,
        "is_in_stock": true
    },
    "special_price": "899.000000",
}

export const sortOptions = [
    {
        value: 'recommended',
        label: 'Recommended',
    },
    {
        value: 'popularity',
        label: 'Popularity',
    },
    {
        value: 'name_asc',
        label: 'Name: A to Z',
    },
    {
        value: 'name_desc',
        label: 'Name: Z to A',
    },
    {
        value: 'price_desc',
        label: 'Price: High to Low',
    },
    {
        value: 'price_asc',
        label: 'Price: Low to High',
    }
]

export const dietaryPreference =  [
    {
        value: 'true',
        name: 'is_veg',
        label: 'Veg'
    },
    {
        value: 'false',
        name: 'is_veg',
        label: 'Non Veg'
    }
]

export const allowedFilters = [
    'category_ids',
    'price', 
    'page', 
    'concern', 
    'brand', 
    'dietary_preference', 
    'form', 
    'sort_by', 
    'q', 
    'item_weight', 
    'oos', 
    'page_size', 
    'is_hl_verified', 
    'primary_l2_category', 
    'flavour',
    'offers',
    'discount',
    'form',
    'recommended_use',
    'gender',
    'weight_quantity',
    'pack_of',
    'special_info',
    'is_hm_verified',
    'diet_type',
    'pack_size'
]

export const staticFilter = [
    {
        label: 'Price',
        attribute_code: 'price'
    }
    // {
    //     label: 'Rating',
    //     attribute_code: 'ratings'
    // }
]

export const ratings = [
    {
      value: '4-5',
      label: 4,
      name: 'rating'
    },
    {
      value: '3-5',
      label: 3,
      name: 'rating'
    },
    {
      value: '2-5',
      label: 2,
      name: 'rating'
    },
    {
      value: '1-5',
      label: 1,
      name: 'rating'
    }
  ]

export const priceValues = [
    {
        label: '1000 & below',
        value: '0-1000'
    }
]

export const sortBy = [
    {
        value: 'recommended',
        title: 'Recommended',
        id: 'Recommended'
    },
    {
        value: 'popularity',
        title: 'Popularity',
        id: 'Popularity'
    },
    {
        value: 'name_asc',
        title: 'Name: A to Z',
        id: 'Name: A to Z'
    },
    {
        value: 'name_desc',
        title: 'Name: Z to A',
        id: 'Name: Z to A'
    },
    {
        value: 'price_desc',
        title: 'Price: High to Low',
        id: 'Price: High to Low'
    },
    {
        value: 'price_asc',
        title: 'Price: Low to High',
        id: 'Price: Low to High'
    }
  ];

export const PaginationStyle = "relative block py-2 px-3 ml-[-1px] border-[1px] border-[#dee2e6]"

export const sliderUrls = {
    'health-supplements' : {
        spotlight: 'in-the-spotlight-health-supplements',
        popularlyBought: 'popularly-bought-health-supplements',
        primary: 'primary-carousel-health-supplements',
        secondary: 'secondary-carousel-health-supplements'
    },
    'health-foods-beverages' : {
        spotlight: 'in-the-spotlight-health-foods-beverages',
        popularlyBought: 'popularly-bought-health-foods-beverages',
        primary: 'primary-carousel-health-foods-beverages',
        secondary: 'secondary-carousel-health-foods-beverages'
    },
    'hair-skin-nails' : {
        spotlight: 'in-the-spotlight-hair-skin-nails',
        popularlyBought: 'popularly-bought-hair-skin-nails',
        primary: 'primary-carousel-hair-skin-nails',
        secondary: 'secondary-carousel-hair-skin-nails'
    },
    'weight-management' : {
        spotlight: 'in-the-spotlight-weight-management',
        popularlyBought: 'popularly-bought-weight-management',
        primary: 'primary-carousel-weight-management',
        secondary: 'secondary-carousel-weight-management'
    },
    'intimate-health' : {
        spotlight: 'in-the-spotlight-intimate-health',
        popularlyBought: 'popularly-bought-intimate-health',
        primary: 'primary-carousel-intimate-health',
        secondary: 'secondary-carousel-intimate-health'
    },
    'sports-nutrition' : {
        spotlight: 'in-the-spotlight-sports-nutrition',
        popularlyBought: 'popularly-bought-sports-nutrition',
        primary: 'primary-carousel-sports-nutrition',
        secondary: 'secondary-carousel-sports-nutrition'
    },
    'women-s-health' : {
        spotlight: 'in-the-spotlight-women-s-health',
        popularlyBought: 'popularly-bought-women-s-health',
        primary: 'primary-carousel-women-s-health',
        secondary: 'secondary-carousel-women-s-health'
    },
    'kids-nutrition' : {
        spotlight: 'in-the-spotlight-kids-nutrition',
        popularlyBought: 'popularly-bought-kids-nutrition',
        primary: 'primary-carousel-kids-nutrition',
        secondary: 'secondary-carousel-kids-nutrition'
    },
    'ayurvedic-herbal-supplements' : {
        spotlight: 'in-the-spotlight-ayurvedic-herbal-supplements',
        popularlyBought: 'popularly-bought-ayurvedic-herbal-supplements',
        primary: 'primary-carousel-ayurvedic-herbal-supplements',
        secondary: 'secondary-carousel-ayurvedic-herbal-supplements'
    },
    'expert-opinion' : {
        spotlight: 'in-the-spotlight-expert-opinion',
        popularlyBought: 'popularly-bought-expert-opinion',
        primary: 'primary-carousel-expert-opinion',
        secondary: 'secondary-carousel-expert-opinion'
    },
    'sexual-wellness' : {
        spotlight: 'in-the-spotlight-sexual-wellness',
        popularlyBought: 'popularly-bought-sexual-wellness',
        primary: 'primary-carousel-sexual-wellness',
        secondary: 'secondary-carousel-sexual-wellness'
    },
    'sexual-health-for-him' : {
        spotlight: 'in-the-spotlight-sexual-health-for-him',
        popularlyBought: 'popularly-bought-sexual-health-for-him',
        primary: 'primary-carousel-sexual-health-for-him',
        secondary: 'secondary-carousel-sexual-health-for-him'
    },
    'sexual-health-for-her' : {
        spotlight: 'in-the-spotlight-sexual-health-for-her',
        popularlyBought: 'popularly-bought-sexual-health-for-her',
        primary: 'primary-carousel-sexual-health-for-her',
        secondary: 'secondary-carousel-sexual-health-for-her'
    },
    'for-kids-5-18' : {
        spotlight: 'in-the-spotlight-for-kids-5-18',
        popularlyBought: 'popularly-bought-for-kids-5-18',
        primary: 'primary-carousel-for-kids-5-18',
        secondary: 'secondary-carousel-for-kids-5-18',
        tertiary: 'tertiary-carousel-for-kids-5-18'
    },
    'for-men-18-29' : {
        spotlight: 'in-the-spotlight-for-men-18-29',
        popularlyBought: 'popularly-bought-for-men-18-29',
        primary: 'primary-carousel-for-men-18-29',
        secondary: 'secondary-carousel-for-men-18-29',
        tertiary: 'tertiary-carousel-for-men-18-29'
    },
    'for-women-18-29' : {
        spotlight: 'in-the-spotlight-for-women-18-29',
        popularlyBought: 'popularly-bought-for-women-18-29',
        primary: 'primary-carousel-for-women-18-29',
        secondary: 'secondary-carousel-for-women-18-29',
        tertiary: 'tertiary-carousel-for-women-18-29'
    },
    'for-men-30-50' : {
        spotlight: 'in-the-spotlight-for-men-30-50',
        popularlyBought: 'popularly-bought-for-men-30-50',
        primary: 'primary-carousel-for-men-30-50',
        secondary: 'secondary-carousel-for-men-30-50',
        tertiary: 'tertiary-carousel-for-men-30-50'
    },
    'for-women-30-50' : {
        spotlight: 'in-the-spotlight-for-women-30-50',
        popularlyBought: 'popularly-bought-for-women-30-50',
        primary: 'primary-carousel-for-women-30-50',
        secondary: 'secondary-carousel-for-women-30-50',
        tertiary: 'tertiary-carousel-for-women-30-50'
    },
    'for-men-51-above' : {
        spotlight: 'in-the-spotlight-for-men-51-above',
        popularlyBought: 'popularly-bought-for-men-51-above',
        primary: 'primary-carousel-for-men-51-above',
        secondary: 'secondary-carousel-for-men-51-above',
        tertiary: 'tertiary-carousel-for-men-51-above'
    },
    'for-women-51-above' : {
        spotlight: 'in-the-spotlight-for-women-51-above',
        popularlyBought: 'popularly-bought-for-women-51-above',
        primary: 'primary-carousel-for-women-51-above',
        secondary: 'secondary-carousel-for-women-51-above',
        tertiary: 'tertiary-carousel-for-women-51-above'
    },
    'combat-hair-fall' : {
        spotlight: 'combat-hair-fall-primary',
        popularlyBought: 'combat-hair-fall-secondary',
        primary: 'combat-hair-fall-tertiary',
        secondary: 'combat-hair-fall-quaternary',
        tertiary: 'combat-hair-fall-quinary'
    },
    'stronger-healthier-hair' : {
        spotlight: 'stronger-healthier-hair-primary',
        popularlyBought: 'stronger-healthier-hair-secondary',
        primary: 'stronger-healthier-hair-tertiary',
        secondary: 'stronger-healthier-hair-quaternary',
        tertiary: 'stronger-healthier-hair-quinary'
    },
    'calm-nights-ahead' : {
        spotlight: 'calm-nights-ahead-primary',
        popularlyBought: 'calm-nights-ahead-secondary',
        primary: 'calm-nights-ahead-tertiary',
        secondary: 'calm-nights-ahead-quaternary',
        tertiary: 'calm-nights-ahead-quinary'
    },
    'weight-free-worry-free' : {
        spotlight: 'weight-free-worry-free-primary',
        popularlyBought: 'weight-free-worry-free-secondary',
        primary: 'weight-free-worry-free-tertiary',
        secondary: 'weight-free-worry-free-quaternary',
        tertiary: 'weight-free-worry-free-quinary'
    },
    'conquer-your-bowel' : {
        spotlight: 'conquer-your-bowel-primary',
        popularlyBought: 'conquer-your-bowel-secondary',
        primary: 'conquer-your-bowel-tertiary',
        secondary: 'conquer-your-bowel-quaternary',
        tertiary: 'conquer-your-bowel-quinary'
    },
    'amplify-athletic-power' : {
        spotlight: 'amplify-athletic-power-primary',
        popularlyBought: 'amplify-athletic-power-secondary',
        primary: 'amplify-athletic-power-tertiary',
        secondary: 'amplify-athletic-power-quaternary',
        tertiary: 'amplify-athletic-power-quinary'
    },
    'healthy-aging-glow' : {
        spotlight: 'healthy-aging-glow-primary',
        popularlyBought: 'healthy-aging-glow-secondary',
        primary: 'healthy-aging-glow-tertiary',
        secondary: 'healthy-aging-glow-quaternary',
        tertiary: 'healthy-aging-glow-quinary'
    },
    'flash-deal-mania': {
        spotlight: 'in-the-spotlight-flash-deal-mania',
        popularlyBought: 'popularly-bought-flash-deal-mania',
        primary: 'primary-carousel-flash-deal-mania',
        secondary: 'secondary-carousel-flash-deal-mania',
        tertiary: 'flash-deal-mania-quinary'
    },
    'sexual-wellness-sale': {
        spotlight: 'in-the-spotlight-sexual-wellness-sale',
        popularlyBought: 'popularly-bought-sexual-wellness-sale',
        primary: 'primary-carousel-sexual-wellness-sale',
        secondary: 'secondary-carousel-sexual-wellness-sale',
        tertiary: 'sexual-wellness-sale-quinary'
    }
}

export const QuickFiltersData = {
	'whey-protein': [
		{
			key: 'brand',
			value: [
				{ label: 'AS-IT-IS Nutrition', id: '2275' },
				{ label: 'Avvatar', id: '2253' },
				{ label: 'Optimum Nutrition ON', id: '1277' },
				{ label: 'My Protein', id: '2676' },
				{ label: 'GNC', id: '1053' },
			],
			header: 'Top Brands'
		},
		{
			key: 'weight_quantity',
			value: [
				{ label: 'Under 500g', id: '2380' },
				{ label: '500-999gm', id: '2544' },
				{ label: '1-1.9 Kg', id: '2535' },
				{ label: '2-2.9 Kg', id: '2536' },
				{ label: '4-4.9 Kg', id: '2538' },
			],
			header: 'Weight'
		},
		{
			key: 'discount',
			value: [
				{ label: '35% or more', id: '2361' },
				{ label: '50% or more', id: '2362' },
				{ label: '60% or more', id: '2363' },
				{ label: '70% or more', id: '2364' },
			],
			header: 'Discounts'
		},
		{
			key: 'flavour',
			value: [
				{ label: 'Cafe Mocha', id: '779' },
				{ label: 'Rich Choclate ', id: '778' },
				{ label: 'Unflavoured', id: '55' },
				{ label: 'Double Rich Choclate ', id: '774' },
				{ label: 'Malai Kulfi', id: '1229' },
				{ label: 'Choclate', id: '1970' },
			],
			header: 'Flavour'
		},
		{
			key: 'concern',
			value: [
				{ label: 'Muscle Gain', id: '962' },
				{ label: 'Muscle Repair / Recovery', id: '960' },
				{ label: 'Hydration & Energy', id: '1114' },
				{ label: 'Weight Management', id: '22' }
			],
			header: 'Concerns'
		}
	],
	'protein-bars-snacks': [
		{
			key: 'brand',
			value: [
				{ label: 'PHAB', id: '329' },
				{ label: 'Fitspire ', id: '458' },
				{ label: 'The Whole truth', id: '1305' },
				{ label: 'RiteBite Max Protein', id: '599' },
			],
			header: 'Top Brands'
		},
		{
			key: 'weight_quantity',
			value: [
				{ label: 'Under 500g', id: '2380' },
				{ label: '500-999gm', id: '2544' },
				{ label: '1-1.9 Kg', id: '2535' },
			],
			header: 'Weight'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
			],
			header: 'Discounts'
		},
	],
	'mass-gainers': [
		{
			key: 'brand',
			value: [
				{ label: 'Nutrabay', id: '576' },
				{ label: 'GNC ', id: '1053' },
				{ label: 'ON', id: '1277' },
				{ label: 'Avvatar', id: '2253' },
			],
			header: 'Top Brands'
		},
		{
			key: 'weight_quantity',
			value: [
				{ label: '1-1.9 Kg', id: '2535' },
				{ label: '2-2.9 Kg', id: '2536' },
				{ label: '4-4.9 Kg', id: '2538' },
			],
			header: 'Weight'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '60% or more', id: '2363' },
			],
			header: 'Discounts'
		},
		{
			key: 'flavour',
			value: [
				{ label: 'Vanilla', id: '871' },
				{ label: 'Chocolate', id: '1970' },
				{ label: 'Mango', id: '47' },
				{ label: 'Banana', id: '1154' },
			],
			header: 'Flavour'
		},
	],
	'creatine': [
		{
			key: 'brand',
			value: [
				{ label: 'AS-IT-IS Nutrition', id: '2275' },
				{ label: 'Nutrabay ', id: '576' },
				{ label: 'GNC', id: '1053' },
				{ label: 'Muscle Asylum', id: '2441' },
			],
			header: 'Top Brands'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '35% or more', id: '2361' },
			],
			header: 'Discounts'
		},
		{
			key: 'concern',
			value: [
				{ label: 'Muscle/Repair Recovery ', id: '960' },
				{ label: 'Muscle Gain', id: '962' },
			],
			header: 'Concern'
		}
	],
	'vegan-plant-based-protein': [
		{
			key: 'brand',
			value: [
				{ label: 'Fast & Up', id: '298' },
				{ label: 'Carbamide Forte ', id: '504' },
				{ label: 'RiteBite Max Protein', id: '599' },
				{ label: 'Oziva', id: '328' },
				{ label: 'Origin Nutrition', id: '2691' },
				{ label: 'NutraBay', id: '576' },
			],
			header: 'Top Brands'
		},
		{
			key: 'weight_quantity',
			value: [
				{ label: 'Under 500g', id: '2380' },
				{ label: '500-999g', id: '2544' },
				{ label: '1-1.9 Kg', id: '2535' }
			],
			header: 'Weight'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '35% or more', id: '2361' },
			],
			header: 'Discounts'
		},
		{
			key: 'flavour',
			value: [
				{ label: 'Unflavoured ', id: '55' },
				{ label: 'Chocolate', id: '1970' },
				{ label: 'Vanilla', id: '871' },
			],
			header: 'Flavour'
		},
		{
			key: 'concern',
			value: [
				{ label: 'Muscle/Repair Recovery ', id: '960' },
				{ label: 'Muscle Gain', id: '962' },
			],
			header: 'Concern'
		}
	],
	'pre-workout': [
		{
			key: 'brand',
			value: [
				{ label: 'NutraBay', id: '576' },
				{ label: 'GNC', id: '298' },
				{ label: "DC DOCTOR'S CHOICE", id: '2100' },
			],
			header: 'Top Brands'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '35% or more', id: '2361' },
			],
			header: 'Discounts'
		},
		{
			key: 'flavour',
			value: [
				{ label: 'Green Apple', id: '45' },
				{ label: 'Unflavoured ', id: '55' },
				{ label: 'Watermelon', id: '56' },
				{ label: 'Fruit Punch', id: '782' },
				{ label: 'Lychee', id: '1225' },
			],
			header: 'Flavour'
		}
	],
	'post-workout': [
		{
			key: 'brand',
			value: [
				{ label: 'Fast & Up', id: '298' },
				{ label: 'Nutrabay', id: '576' },
			],
			header: 'Top Brands'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '35% or more', id: '2361' },
			],
			header: 'Discounts'
		}
	],
	'bcaa': [
		{
			key: 'brand',
			value: [
				{ label: 'Fast & Up', id: '298' },
				{ label: 'HealthyHey Sports', id: '311' },
				{ label: 'NutraBay', id: '576' },
				{ label: 'NeuLife', id: '2672' },
			],
			header: 'Top Brands'
		},
		{
			key: 'weight_quantity',
			value: [
				{ label: 'Under 500g', id: '2380' },
				{ label: '500-999gm', id: '2544' },
				{ label: '1-1.9 Kg', id: '2535' },
			],
			header: 'Weight'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '35% or more', id: '2361' },
			],
			header: 'Discounts'
		},
		{
			key: 'flavour',
			value: [
				{ label: 'Green Apple', id: '45' },
				{ label: 'Watermelon', id: '56' },
				{ label: 'Unflavoured', id: '55' },
				{ label: 'Fruit Punch', id: '782' }
			],
			header: 'Flavour'
		}
	],
	'eaa': [
		{
			key: 'brand',
			value: [
				{ label: 'Fast & Up', id: '298' },
				{ label: "DC Doctor's Choice", id: '2100' },
			],
			header: 'Top Brands'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '35% or more', id: '2361' },
			],
			header: 'Discounts'
		}
	],
	'l-carnitine-and-l-arginine': [
		{
			key: 'brand',
			value: [
				{ label: 'Fast & Up', id: '298' },
				{ label: "Himalayan Organics", id: '313' },
				{ label: "Carbamide Forte", id: '504' },
				{ label: "NutraBay", id: '576' },
			],
			header: 'Top Brands'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '35% or more', id: '2361' },
			],
			header: 'Discounts'
		},
		{
			key: 'concern',
			value: [
				{ label: 'Hydration/Energy', id: '1114' },
				{ label: 'Muscle/Repair Recovery', id: '960' },
			],
			header: 'Concern'
		}
	],
	'sports-drinks': [
		{
			key: 'brand',
			value: [
				{ label: 'Fast & Up', id: '298' },
				{ label: "WishNew Wellness", id: '2689' },
			],
			header: 'Top Brands'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '35% or more', id: '2361' },
			],
			header: 'Discounts'
		}
	],
	'whey-protein-isolates': [
		{
			key: 'brand',
			value: [
				{ label: 'NutraBay', id: '576' },
				{ label: "Isopure", id: '1279' },
				{ label: "My Protein", id: '2676' },
				{ label: "AS-IT-IS Nutrition", id: '2275' },
			],
			header: 'Top Brands'
		},
		{
			key: 'weight_quantity',
			value: [
				{ label: '1-1.9 Kg', id: '2535' },
				{ label: '2-2.9 Kg', id: '2536' },
			],
			header: 'Weight'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '35% or more', id: '2361' },
			],
			header: 'Discounts'
		},
	],
	'zma': [
		{
			key: 'weight_quantity',
			value: [
				{ label: '1-1.9 Kg', id: '2535' },
				{ label: '2-2.9 Kg', id: '2536' },
			],
			header: 'Weight'
		}
	],
	'deals-valid-till-midnight': [
		{
			key: 'primary_l2_category',
			value: [
				{ label: 'Whey Protein', id: '42' },
				{ label: 'Mass Gainers', id: '49' },
				{ label: 'Creatine', id: '1069' },
				{ label: 'BCAA', id: '1068' },
				{ label: 'Protein Bars & Snacks', id: '47' },
			],
			header: 'Categories'
		},
		{
			key: 'brand',
			value: [
				{ label: 'Muscleblaze MB', id: '2138' },
				{ label: 'AS-IT-IS Nutrition', id: '2275' },
				{ label: 'Avvatar', id: '2253' },
				{ label: 'Optimum Nutrition ON', id: '1277' },
				{ label: 'MYPROTEIN', id: '2676' },
				{ label: 'GNC', id: '1053' },
			],
			header: 'Top Brands'
		},
		{
			key: 'weight_quantity',
			value: [
				{ label: '1-1.9 Kg', id: '2535' },
				{ label: '2-2.9 Kg', id: '2536' },
				{ label: '3-3.9 Kg', id: '2537' },
				{ label: '500-999gm', id: '2544' },
			],
			header: 'Weight'
		},
		{
			key: 'discount',
			value: [
				{ label: '35% or more', id: '2361' },
				{ label: '50% or more', id: '2362' },
				{ label: '60% or more', id: '2363' },
				{ label: '70% or more', id: '2364' }
			],
			header: 'Discounts'
		},
		{
			key: 'flavour',
			value: [
				{ label: 'Rich Chocolate', id: '1017' },
				{ label: 'Dark Chocolate', id: '1356' },
				{ label: 'Kulfi', id: '767' },
				{ label: 'Vanilla', id: '871' }
			],
			header: 'Flavour'
		},
		{
			key: 'concern',
			value: [
				{ label: 'Muscle Gain', id: '962' },
				{ label: 'Muscle Repair / Recovery', id: '960' },
				{ label: 'Hydration & Energy', id: '1114' },
				{ label: 'Weight Management', id: '22' }
			],
			header: 'Concerns'
		},
	],
	'health-supplements': [
		{
			key: 'category_ids',
			value: [
				{ label: 'Multivitamins', id: '10' },
				{ label: 'Omega 3 & Fish Oil', id: '11' },
				{ label: 'Pre & Probiotics', id: '8' },
				{ label: 'Vitamin C (Immunity)', id: '1158' },
				{ label: 'Melatonin (Sleep Care)', id: '7' },
			],
			header: 'Categories'
		},
		{
			key: 'form',
			value: [
				{ label: 'Powder', id: '382' },
				{ label: 'Gummy', id: '465' },
				{ label: 'Capsules', id: '359' },
				{ label: 'Candy', id: '358' },
			],
			header: 'Form'
		},
		{
			key: 'concern',
			value: [
				{ label: 'Sleep, Stress & Mental Wellness', id: '21' },
				{ label: 'Immunity', id: '16' },
				{ label: 'Brain Booster', id: '7' },
				{ label: 'Bone & Joint Health', id: '963' },
			],
			header: 'Concerns'
		},
		{
			key: 'brand',
			value: [
				{ label: `What's Up Wellness`, id: '534' },
				{ label: 'Carbamide Forte', id: '504' },
				{ label: 'Himalayan Organics', id: '313' },
				{ label: 'Wellbeing Nutrition', id: '347' },
			],
			header: 'Top Brands'
		},
		{
			key: 'diet_type',
			value: [
				{ label: 'Jain', id: '2335' },
				{ label: 'Veg', id: '2328' },
				{ label: 'Non Veg', id: '2329' },
				{ label: 'Keto', id: '2331' }
			],
			header: 'Diet'
		},
		{
			key: 'discount',
			value: [
				{ label: '35% or more', id: '2361' },
				{ label: '50% or more', id: '2362' },
				{ label: '60% or more', id: '2363' },
				{ label: '70% or more', id: '2364' }
			],
			header: 'Discounts'
		}
	],
	'sports-nutrition': [
		{
			key: 'category_ids',
			value: [
				{ label: 'Whey Protein', id: '42' },
				{ label: 'Mass Gainers', id: '49' },
				{ label: 'Creatine', id: '1069' },
				{ label: 'BCAA', id: '1068' },
				{ label: 'Protein Bars & Snacks', id: '47' },
				{ label: 'Pre Workout', id: '1073' },
			],
			header: 'Categories'
		},
		{
			key: 'brand',
			value: [
				{ label: 'Fast & Up', id: '298' },
				{ label: 'Phab', id: '329' },
				{ label: 'Carbamide Forte', id: '504' },
				{ label: 'BOLT', id: '566' },
				{ label: 'Optimum Nutrition ON', id: '1277' },
				{ label: 'NutraBay', id: '576' },
				{ label: 'The Whole Truth', id: '1305' },
			],
			header: 'Top Brands'
		},
		{
			key: 'weight_quantity',
			value: [
				{ label: 'Under 500g', id: '2380' },
				{ label: '500-999gm', id: '2544' },
				{ label: '1-1.9 Kg', id: '2535' },
				{ label: '2-2.9 Kg', id: '2536' },
			],
			header: 'Weight'
		},
		{
			key: 'discount',
			value: [
				{ label: '10% or more', id: '2359' },
				{ label: '25% or more', id: '2360' },
				{ label: '35% or more', id: '2361' },
			],
			header: 'Discounts'
		},
		{
			key: 'price',
			value: [
				{ label: 'Under ₹10000', id: '0_10000' }
			],
			header: 'Price'
		},
	],
	'health-foods-beverages': [
		{
			key: 'category_ids',
			value: [
				{ label: 'Peanut Butter', id: '1155' },
				{ label: 'Dry Fruits, Nuts & Trail Mixes', id: '18' },
				{ label: 'Muesli, Cereals & More', id: '24' },
				{ label: 'Wellness Coffee & Tea', id: '28' },
			],
			header: 'Categories'
		},
		{
			key: 'brand',
			value: [
				{ label: 'Happilo', id: '1177' },
				{ label: 'Alpino', id: '486' },
				{ label: 'MyFitness', id: '323' },
				{ label: 'Yogabar', id: '349' },
				{ label: 'Pintola', id: '330' }
			],
			header: 'Top Brands'
		},
		{
			key: 'discount',
			value: [
				{ label: '35% or more', id: '2361' },
				{ label: '50% or more', id: '2362' },
				{ label: '60% or more', id: '2363' },
				{ label: '70% or more', id: '2364' }
			],
			header: 'Discounts'
		},
		{
			key: 'diet_type',
			value: [
				{ label: 'Sugar Free', id: '2534' },
				{ label: 'Gluten Free', id: '2330' },
				{ label: 'High Fiber', id: '2333' },
				{ label: 'High Protein', id: '2334' }
			],
			header: 'Diet'
		},
		{
			key: 'form',
			value: [
				{ label: 'Cookies', id: '363' },
				{ label: 'Dried Fruits', id: '367' },
				{ label: 'Dried Berries', id: '366' },
				{ label: 'Bars', id: '355' },
			],
			header: 'Form'
		},
		{
			key: 'weight_quantity',
			value: [
				{ label: '500 - 999 gram', id: '2544' },
				{ label: '1 - 1.9 Kg', id: '2535' },
				{ label: '2 - 2.9 Kg', id: '2536' },
				{ label: '5 Kg & above', id: '2539' },
			],
			header: 'Weight'
		},
	],
	'hair-skin-nails': [
		{
			key: 'category_ids',
			value: [
				{ label: 'Collagen (Skin)', id: '32' },
				{ label: 'Biotin', id: '31' },
				{ label: 'Glutathione', id: '33' },
				{ label: 'Hair, Skin & Nails Combos', id: '930' },
			],
			header: 'Categories'
		},
		{
			key: 'concern',
			value: [
				{ label: 'Beauty', id: '935' },
				{ label: 'Hair', id: '2456' },
			],
			header: 'Concerns'
		},
		{
			key: 'brand',
			value: [
				{ label: `What's Up Wellness`, id: '534' },
				{ label: 'Oziva', id: '328' },
				{ label: 'Chicnutrix', id: '518' },
				{ label: 'Plix', id: '331' },
				{ label: 'Wellbeing Nutrition', id: '347' },
			],
			header: 'Top Brands'
		},
		{
			key: 'form',
			value: [
				{ label: 'Gummies', id: '465' },
				{ label: 'Capsules', id: '2780' },
				{ label: 'Effervescent', id: '1219' },
				{ label: 'Powder', id: '382' },
			],
			header: 'Form'
		},
		{
			key: 'discount',
			value: [
				{ label: '35% or more', id: '2361' },
				{ label: '50% or more', id: '2362' },
				{ label: '60% or more', id: '2363' },
				{ label: '70% or more', id: '2364' }
			],
			header: 'Discounts'
		},
		{
			key: 'diet_type',
			value: [
				{ label: 'Veg', id: '2328' },
				{ label: 'Jain', id: '2335' },
				{ label: 'Non Veg', id: '2329' },
				{ label: 'Halal', id: '2332' },
			],
			header: 'Diet'
		},
	],
	'weight-management': [
		{
			key: 'category_ids',
			value: [
				{ label: 'Apple Cider Vinegar', id: '37' },
				{ label: 'Sugar Substitutes', id: '414' },
				{ label: 'Weight Loss', id: '415' },
				{ label: 'Green Coffee beans', id: '39' },
				{ label: 'Slimming and Detox Shakes', id: '40' },
			],
			header: 'Categories'
		},
		{
			key: 'diet_type',
			value: [
				{ label: 'Veg', id: '2328' },
				{ label: 'Jain', id: '2335' },
				{ label: 'High Protein', id: '2334' },
				{ label: 'Non Veg', id: '2329' },
				{ label: 'Plant Based', id: '2548' }
			],
			header: 'Diet'
		},
		{
			key: 'brand',
			value: [
				{ label: 'Plix', id: '331' },
				{ label: 'Chicnutrix', id: '518' },
				{ label: 'Kapiva', id: '319' },
				{ label: 'Oziva', id: '328' },
				{ label: 'Wellbeing Nutrition', id: '347' },
			],
			header: 'Top Brands'
		},
		{
			key: 'form',
			value: [
				{ label: 'Powder', id: '382' },
				{ label: 'Liquid', id: '375' },
				{ label: 'Capsules', id: '359' },
				{ label: 'Tablets', id: '391' },
				{ label: 'Effervescent', id: '1219' },
			],
			header: 'Form'
		},
	],
	'ayurvedic-herbal-supplements': [
		{
			key: 'category_ids',
			value: [
				{ label: 'Ashwagandha (Anti-Stress)', id: '67' },
				{ label: 'Constipation, Bloating & Acidity', id: '70' },
				{ label: 'Pain Relief', id: '456' },
				{ label: 'Triphala (Antioxidants, Digestion)', id: '78' },
			],
			header: 'Categories'
		},
		{
			key: 'concern',
			value: [
				{ label: 'Brain Booster', id: '7' },
				{ label: 'Heart Care', id: '936' },
				{ label: 'Immunity', id: '16' },
				{ label: 'Sleep, Stress & Mental Wellness', id: '21' },
				{ label: 'Liver Care', id: '961' },
			],
			header: 'Concerns'
		},
		{
			key: 'brand',
			value: [
				{ label: 'Kapiva', id: '319' },
				{ label: 'Himalayan Organics', id: '313' },
				{ label: 'Vansaar', id: '2274' },
				{ label: 'Organic India', id: '604' },
				{ label: `Dr Vaidya's`, id: '564' },
			],
			header: 'Top Brands'
		},
		{
			key: 'discount',
			value: [
				{ label: '35% or more', id: '2361' },
				{ label: '50% or more', id: '2362' },
				{ label: '60% or more', id: '2363' },
				{ label: '70% or more', id: '2364' }
			],
			header: 'Discounts'
		},
		{
			key: 'form',
			value: [
				{ label: 'Capsules', id: '2780' },
				{ label: 'Liquid', id: '375' },
				{ label: 'Powder', id: '382' },
				{ label: 'Berries', id: '366' },
				{ label: 'Tablets', id: '391' },
			],
			header: 'Form'
		},
		{
			key: 'diet_type',
			value: [
				{ label: 'Veg', id: '2328' },
				{ label: 'Jain', id: '2335' },
				{ label: 'Non Veg', id: '2329' },
			],
			header: 'Diet'
		}
	],
	'kids-nutrition': [
		{
			key: 'category_ids',
			value: [
				{ label: 'Calcium, D3, Multivitamins & more', id: '63' },
				{ label: 'Protein to Grow', id: '59' },
			],
			header: 'Categories'
		},
		{
			key: 'brand',
			value: [
				{ label: 'Wellbeing Nutrition', id: '347' },
				{ label: 'Gritzo', id: '303' },
				{ label: 'HealthBest', id: '306' },
				{ label: 'Zingavita', id: '350' },
			],
			header: 'Top Brands'
		},
		{
			key: 'discount',
			value: [
				{ label: '35% or more', id: '2361' },
				{ label: '50% or more', id: '2362' },
				{ label: '60% or more', id: '2363' },
			],
			header: 'Discounts'
		},
		{
			key: 'diet_type',
			value: [
				{ label: 'Jain', id: '2335' },
				{ label: 'Veg', id: '2328' },
				{ label: 'Non Veg', id: '2329' },
				{ label: 'High Protein', id: '2334' }
			],
			header: 'Diet'
		},
		{
			key: 'form',
			value: [
				{ label: 'Powder', id: '382' },
				{ label: 'Liquid', id: '375' },
				{ label: 'Gummy', id: '465' },
			],
			header: 'Form'
		},
	],
	'women-s-health': [
		{
			key: 'category_ids',
			value: [
				{ label: 'Women Supplements', id: '54' },
				{ label: 'PCOD / PCOS & Fertility Care', id: '55' },
				{ label: 'Period Pain Relief', id: '52' },
				{ label: 'Protein For Women', id: '53' },
			],
			header: 'Categories'
		},
		{
			key: 'diet_type',
			value: [
				{ label: 'Veg', id: '2328' },
				{ label: 'Jain', id: '2335' },
				{ label: 'Non Veg', id: '2329' },
				{ label: 'Plant Based', id: '2548' }
			],
			header: 'Diet'
		},
		{
			key: 'brand',
			value: [
				{ label: 'Carbamide Forte', id: '504' },
				{ label: 'PRO360', id: '535' },
				{ label: 'Gynoveda', id: '543' },
				{ label: 'Inaari', id: '603' },
				{ label: 'Wellbeing Nutrition', id: '347' },
			],
			header: 'Top Brands'
		},
		{
			key: 'form',
			value: [
				{ label: 'Powder', id: '382' },
				{ label: 'Liquid', id: '375' },
				{ label: 'Capsules', id: '359' },
				{ label: 'Tablets', id: '391' },
			],
			header: 'Form'
		},
	],
	'sexual-health-for-him': [],
	'sexual-health-for-her': [],
	'best-deals': [
		{
			key: 'primary_l2_category',
			value: [
				{ label: 'Whey Protein', id: '42' },
				{ label: 'Peanut Butter', id: '1155' },
				{ label: 'Creatine', id: '1069' },
				{ label: 'Muesli, Cereals & more', id: '24' },
				{ label: 'Whey Protein Isolates', id: '1638' },
				{ label: 'Protein Bars & Snacks', id: '47' },
				{ label: 'BCAA', id: '1068' },
				{ label: 'Omega 3 & fish Oil', id: '11' },
			],
			header: 'Categories'
		},
		{
			key: 'brand',
			value: [
				{ label: 'AS-IT-IS Nutrition', id: '2275' },
				{ label: 'Avvatar', id: '2253' },
				{ label: 'Muscleblaze', id: '2138' },
				{ label: 'Healthfarm', id: '1951' },
				{ label: 'Optimum Nutrition', id: '1277' },
				{ label: 'Nakpro', id: '3501' },
				{ label: 'The Whole Truth', id: '1305' },
				{ label: 'Pintola', id: '330' },
			],
			header: 'Top Brands'
		},
	]
};

export const inlineWidget = (templateId, data) => {
	switch (templateId) {
		case "1":
			return <InformativeWidget
				data={data} />
		case "2":
			return <FitnessWidget
				data={data} />
		case "3":
			return <TopCategoriesWidget
				data={data} />
		case "4":
			return <SkinCareWidget
				data={data} />
		case "5":
			return <HealthBlogsWidget
				data={data} />
		default:
			return "";
	};
};

export const bestDealscategordIds ={}

export const PercentBadge = ({color}) => (
	<svg
	  width="20"
	  height="20"
	  viewBox="0 0 24 24"
	  fill="none"
	  xmlns="http://www.w3.org/2000/svg"
	>
	  <path
		d="M8.99044 14.9934L14.9903 8.99282M20.9899 11.994C20.9899 13.2624 20.3603 14.3838 19.3966 15.0625C19.598 16.2238 19.2503 17.4618 18.3537 18.3586C17.457 19.2554 16.2192 19.603 15.058 19.4016C14.3793 20.3653 13.2582 20.9949 11.9901 20.9949C10.7219 20.9949 9.60083 20.3654 8.92216 19.4017C7.7608 19.6034 6.52272 19.2557 5.62589 18.3588C4.72906 17.4618 4.38145 16.2236 4.58306 15.0622C3.61963 14.3834 2.99023 13.2622 2.99023 11.994C2.99023 10.7258 3.61968 9.60457 4.58318 8.92582C4.38168 7.76442 4.7293 6.52634 5.62605 5.62949C6.52282 4.73262 7.76077 4.38496 8.92206 4.5865C9.60071 3.62277 10.7219 2.99316 11.9901 2.99316C13.2582 2.99316 14.3793 3.62272 15.058 4.58638C16.2193 4.38474 17.4574 4.73239 18.3542 5.62932C19.251 6.52624 19.5987 7.76443 19.3971 8.92591C20.3605 9.60467 20.9899 10.7258 20.9899 11.994ZM9.74042 9.74289H9.74792V9.75039H9.74042V9.74289ZM10.1154 9.74289C10.1154 9.95002 9.94752 10.1179 9.74042 10.1179C9.53332 10.1179 9.36543 9.95002 9.36543 9.74289C9.36543 9.53576 9.53332 9.36785 9.74042 9.36785C9.94752 9.36785 10.1154 9.53576 10.1154 9.74289ZM14.2403 14.2433H14.2478V14.2508H14.2403V14.2433ZM14.6153 14.2433C14.6153 14.4504 14.4474 14.6184 14.2403 14.6184C14.0332 14.6184 13.8653 14.4504 13.8653 14.2433C13.8653 14.0362 14.0332 13.8683 14.2403 13.8683C14.4474 13.8683 14.6153 14.0362 14.6153 14.2433Z"
		stroke={color}
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
	  />
	</svg>
  );
  