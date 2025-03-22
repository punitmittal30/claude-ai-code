import { filterDataLayer } from 'analytics/datalayer/navigate'
import { filterFbq } from 'analytics/fbq/navigate'
import { generateSource } from 'analytics/helpers'
import { filterSelectTracker } from 'analytics/trackers/navigate'
import SelectDropdown from 'modules/Account/components/Profile/SelectDropdown'
import Link from 'next/link'
import React, { useEffect, useMemo, useState } from 'react'
import { useDispatch, useSelector } from 'react-redux'
import ProductCard from 'shared/components/ProductCard'
import { setFilterValues, setFrequentlyBought, setProdAddedSource } from '../redux/reducer'
import { productInfo, sortBy, sortOptions } from './constants'
import FacetChip from './FacetChip'
import { trackFilterSelect } from 'analytics/plugins/webEngage/navigate'
import { useRouter } from "next/router";
import { inlineWidget } from './constants'
import InformativeWidget from './inlineWidgets/informativeWidget';
import TopCategoriesWidget from './inlineWidgets/TopCategoriesWidget';
import FitnessWidget from "./inlineWidgets/FitnessWidget";
import SkinCareWidget from "./inlineWidgets/SkinCareWidget";
import HealthBlogsWidget from "./inlineWidgets/HealthBlogsWidget";
import BoughtTogether from 'shared/components/ProductCard/BoughtTogether/BoughtTogether'
import { createPortal } from 'react-dom'
import useResize from 'hooks/useResize'
import { useFeatureFlagVariantKey } from 'posthog-js/react'
const trackSortBy = (value) => {
  const trackingData = {
      name: "sort-by", source: generateSource()?.clickSource, value, featureSource: "normal_filters",
  };
  filterDataLayer(trackingData, { featureSource: "normal_filters" });
  filterFbq(trackingData)
  filterSelectTracker({ filter: trackingData });
  trackFilterSelect({ filter: trackingData  })
}

const CategoryListing = ({
  products, selectedFilter, device, filterState, loading, singleLayout, listingSource, isSearch, ssrDevice, inlineWidgetData
}) => {
  const route = useRouter()
  const featureValue = isSearch ? route?.query?.q.trim() : null;
  const dispatch = useDispatch()
  const {isMobile } = useResize()
  const [sortValue, setSortVal] = useState( 'Sort by')
  const selectedFilters = useSelector((state) => state.category.selectedFilter);
  const isBrandAndBenefits = ['/brands/[slug]','/benefits/[slug]'].includes(route.pathname)
  const freqBoughtProd = useSelector((state)=> state.category.frequentlyBought)
  const prodAddedSource = useSelector((state)=>state.category. productAddedSource)
  const [portalContainer, setPortalContainer] = useState(null)
  const product = useSelector((state) => state.category.variants);
  const cartItems = useSelector((state)=>state.cart.data.items)
  const plpLayoutVariation = useFeatureFlagVariantKey('PLP_LAYOUT_VARIATIONS')
  
  const handleSortBy = (event, attr) => {
        const productListing = document.querySelector('.product-category-cards')
    setSortVal(event)
    dispatch(setFilterValues({ field: attr, value: event }));
    trackSortBy(event)
    setTimeout(() => {
      if(productListing) {
          productListing?.scrollIntoView({ behavior: 'smooth' })
      }
  }, 500)
  }

  const groupProducts = (products, groupSize) => {
    if (inlineWidgetData.length) {
      const containsPriorityZero = inlineWidgetData.some(item => item.priority === "0");
      let count = 0;
      for (let i = 0; i < products.length; i++) {
        for (let j = 0; j < inlineWidgetData.length; j++) {
          if (inlineWidgetData[j]?.priority != "0" && inlineWidgetData[j]?.priority * groupSize == i) {
            if(JSON.stringify(products[i + count]) !== JSON.stringify(inlineWidgetData[j])) {
              products.splice(i + count, 0, inlineWidgetData[j]);
            }
            count += 1;
          };
        };
        if (containsPriorityZero && count == (inlineWidgetData.length - 1)) break;
        else if (count == inlineWidgetData.length) break;
      };
      return products;
      
    };
  
  };
 

  useEffect(() => {
    if(filterState['sort_by']) {
      setSortVal(filterState['sort_by'])
    } else {
        setSortVal('recommended')
    }
  }, [filterState])

  const groupedProductData = useMemo(() => {
    if (products && products.length && inlineWidgetData.length !== 0) {
      return groupProducts([...products], 4);
    }
       return products
  }, [products, inlineWidgetData]);


  useEffect(() => {
    const timeout = setTimeout(() => {
      if (prodAddedSource === 'frequentlyBoughtCarosel' || !isMobile) return;
  
      const findItem = cartItems.find(item => Number(item.product_id) === Number(product.id));
  
      if (isMobile && plpLayoutVariation == 'grid') {
        
        if (freqBoughtProd?.fbp?.length > 0 && findItem) {
          const target = document.querySelector(`.${freqBoughtProd.id}`);
          const garbageEl = document.querySelector('.listing-container .bought-together');
  
          if (garbageEl) {
            garbageEl.remove();
          }
  
          const container = document.createElement('div');
          container.classList.add('bought-together', 'col-span-2');
          target.insertAdjacentElement('afterend', container);
          setPortalContainer(container);
        } else {
          const garbageEl = document.querySelector('.listing-container .bought-together');
          if (garbageEl) {
            garbageEl.remove();
          }
          setPortalContainer(null);
        }
      }
      dispatch(setProdAddedSource(''));
    }, 100);
  
    return () => clearTimeout(timeout);
  }, [freqBoughtProd?.fbp, cartItems, prodAddedSource]);
  

  useEffect(()=>{
dispatch(setFrequentlyBought([]))
  },[route])


  let productCardIndex = 0
    
  return (
    <div
      className={`category-listing-container block ${
        device !== "mobile" ? "mb-24" : ""
      }`}
    >
      <div className="sort-by-category hidden sm:flex justify-end bottom-3 mt-3 relative">
        <SelectDropdown
          list={sortBy}
          value={sortValue}
          styleClass={`max-w-[220px] h-[50px] relative bottom-[10px]`}
          defaultValue={"Recommended"}
          update={(e) => handleSortBy(e, "sort_by")}
          customChevron={"/assets/images/chevron-down.svg"}
        />
      </div>
      {(isBrandAndBenefits || device !== 'mobile') && JSON.stringify(filterState) !== "{}" && !route?.asPath?.includes('/product-category/offer-zone') ? (
        <FacetChip
          selectedFacets={selectedFilter}
          isMobile={device == "mobile" ? true : false}
          selectedFilters={selectedFilters}
          featureSource={`normal-filters`}
          isBrandsAndBenefits={isBrandAndBenefits} />
        
      ) : null}
      {device !== "mobile" &&
      inlineWidgetData.length !== 0 &&
      inlineWidgetData[0]?.priority == "0"
        ? inlineWidget(inlineWidgetData[0]?.template, inlineWidgetData[0])
        : ""}
            {groupedProductData && groupedProductData.length !== 0 ? (
          <div className={`listing-container grid ${ plpLayoutVariation == 'grid' ? "grid-cols-2 gap-x-2 gap-y-5":"grid-cols-1"} sm:justify-items-center sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-0 sm:gap-0 md:gap-2 lg:gap-x-3 lg:gap-y-6 sm:relative sm:bottom-[5px]`}>
            {groupedProductData?.map((product, index) => {
              if (product?.banners) {
                const Widget = {
                  1: InformativeWidget,
                  2: FitnessWidget,
                  3: TopCategoriesWidget,
                  4: SkinCareWidget,
                  5: HealthBlogsWidget,
                }[product?.template];

                return Widget ? (
                  <div
                    key={`widget-${index}`} 
                    className={`${plpLayoutVariation == 'grid' ? "col-span-2":" "} sm:col-span-4 sm:justify-self-start`}
                  >
                    <Widget data={product} />
                  </div>
                ) : null;
              } else {
                productCardIndex++;
                return (
                  <div
                    key={`product-${product.sku}`} 
                    className={`product-${productCardIndex} ${ (plpLayoutVariation == 'grid') ? 
                      (productCardIndex % 2 === 0 ? 'pr-4 sm:pr-0' : 'pl-4 sm:pl-0' ) :''
                    } w-full`}
                  >
                    <div className="product-category-list overflow-x-hidden">
                      <ProductCard
                        isListing={true}
                        snapWidget
                        isMobile={device}
                        singleLayout={singleLayout}
                        ssrDevice={ssrDevice}
                        product={product}
                        isGridView={plpLayoutVariation == 'grid'}
                        featureSource={`${listingSource}-listing`}
                        featureValue={featureValue}
                        featurePosition={productCardIndex}
                        selectedFilters={selectedFilter}
                        featureClass={`product-${
                          productCardIndex % 2 === 0
                            ? productCardIndex
                            : productCardIndex + 1
                        }`}
                      />
                    </div>
                    {plpLayoutVariation == 'grid' ? null: <div className="w-full bg-[#F4F4F9] h-2 relative  sm:hidden"></div>  }
                  </div>
                );
              }
            })}
          </div>
        ) : !loading ? (
          <div className="flex justify-center items-center mt-6">
            <div>
              <img
                src="/assets/images/no_result.svg"
                className="w-[250px] h-[250px] sm:w-[400px] sm:h-[400px] object-cover"
                alt="No Results"
              />
              <p className="text-gray-100 text-lg font-semibold text-center pt-4 pb-6">
                No Results Found
              </p>
            </div>
          </div>
        ) : null}

      {(freqBoughtProd?.fbp?.length > 0 && portalContainer && (plpLayoutVariation == 'grid') ) ? createPortal(
        <div className="bought-together sm:hidden">
          <BoughtTogether prod={freqBoughtProd?.fbp} />
        </div>
      , portalContainer) : null}
    </div>
  );
}

export default CategoryListing