import React, {useEffect, useMemo, useState} from 'react';
import useResize from "hooks/useResize";
import {shopByCategorySliderSettings} from "shared/components/ProductSlider/constants";
import Slider from "@ant-design/react-slick";
import {catchErrors, compressImage} from "utils/index";
import {fetchShopByCategory} from "modules/ProductListing/redux/reducer";
import {useDispatch} from "react-redux";
import Skeleton from "react-loading-skeleton";
import Image from "next/image";
import ImgPlaceHolder from 'public/assets/images/asset-placeholder.jpeg';
import getConfig from "next/config";
import {useRouter} from "next/router";
import {XIcon} from "@heroicons/react/outline";
import { useInView } from 'react-intersection-observer';
import { featureImpressionTracker } from 'analytics/trackers/impressions';
import { featureImpressionWebEngage } from 'analytics/plugins/webEngage/impressions';
import { featureImpressionDataLayer } from 'analytics/datalayer/impressions';
import { interactionTracker } from 'analytics/trackers/navigate';
import { interactionDataLayer } from 'analytics/datalayer/navigate';
import { trackInteraction } from 'analytics/plugins/webEngage/navigate';
import { phFeatureImpression } from 'analytics/plugins/postHog/impressions';

const {publicRuntimeConfig} = getConfig();

const ShopByCategory = ({slug, position}) => {
    const dispatch = useDispatch()
    const router = useRouter()
    const screen = useResize()

    const [loading, setLoading] = useState(true)
    const [categories, setCategories] = useState([])
    const [showSort, setShowSort] = useState(false)

    useEffect(() => {
        console.log('slug for shopcategories', slug)
        fetchCategories()
    }, [slug])

    const fetchCategories = async () => {
        try {
            const data = {
                method: "get",
                url: `/catalog/category/slug/${slug}/shop-by-category`,
            };

            const resp = await dispatch(fetchShopByCategory(data)).unwrap();

            if (!resp.data.status) {
                if(!resp.data.message) {
                    console.error(resp.data)
                    return
                }
            }

            setCategories(resp.data.data);
        } catch (e) {
            setCategories([])
            // catchErrors(e);
        } finally {
            setLoading(false)
        }
    }

    const listPfCategories = useMemo(() => {
        if (showSort) return categories

        const temp = [...categories]

        return temp.slice(0, 8)

    }, [showSort, categories])

    const toLink = (category, idx) => {
        hideSort();
        const trackingData = {
            id: "shop-by-category",
            value: `/product-category/${category.slug}`,
            position: idx
        };
        interactionTracker({ action: "content-block-click", entity: trackingData});
        trackInteraction({ action: "content-block-click", entity: trackingData});
        interactionDataLayer("content-block-click", trackingData);
        router.push(`/product-category/${category.slug}`);
    }


    const hideSort = () => {
        document.querySelector('html').style.overflow = ''
        setShowSort(false)
    }

    const trackingData = {
        position: position,
        entity: {
            id: slug,
            type: "shop-by-category",
        },
    };

    const { ref } = useInView({ 
        threshold: 0,
        triggerOnce: true,
        onChange: (inView) => {
            if (inView) {
                featureImpressionTracker(trackingData);
                featureImpressionWebEngage(trackingData);
                featureImpressionDataLayer(trackingData);
                phFeatureImpression(trackingData);
            }
        }, 
    });


    return (
        <div ref={ref}>
            {
                loading ? <div className="mx-auto max-w-7xl mb-5 lg:px-7 pt-2 px-4 pb-0 sm:py-6">
                    <Skeleton className="min-w-[150px] max-w-[50px] col-span-1 mb-2" count={1} />
                    <Skeleton
                        containerClassName={`grid ${screen.device !== 'mobile' ? 'grid-cols-2' : 'grid-cols-3' }  sm:grid-cols-5 gap-[1rem]`}
                        inline={true}
                        count={screen.device !== 'mobile' ? 5 : 9}
                        height={screen.device !== 'mobile' ? 220 : 120}
                    />{" "}
                </div> :
         (listPfCategories.length !== 0 || categories.length !== 0) ? <div className="sm:bg-[#FFFAFA] mb-8 sm:mt-8">
        <div className="mx-auto max-w-7xl lg:px-7 pt-2 pb-0 sm:py-6">
       <div className="w-full bg-[#F4F4F9] h-2 flex sm:hidden mb-4" />
         <div className="px-4 lg:px-0">
            <h2 className="text-lg md:text-4xl font-semibold tracking-tight text-gray-100 mb-4">Shop By Category</h2>
            {
                screen.device !== 'mobile'
                    ?
                                <Slider {...shopByCategorySliderSettings}>
                                    {categories.map((category, idx) => {
                                        return (
                                            <div key={category.id} onClick={() => toLink(category, idx)}
                                                 className="flex justify-center rounded-[12px] overflow-hidden cursor-pointer">
                                                {
                                                    category.desktop_image ? <img
                                                            src={`${publicRuntimeConfig.magentoImageUrl}${compressImage(category.desktop_image, 230, 220)}`}
                                                            alt="category-image"
                                                            width={230}
                                                            loading="lazy"
                                                            height={220}
                                                            />
                                                        : <div className="mx-auto h-[220px] w-[230px]">
                                                            <Image
                                                                src={ImgPlaceHolder}
                                                                className="w-full h-full cursor-pointer"
                                                                alt="image-placeholder"
                                                            />
                                                        </div>
                                                }
                                            </div>
                                        );
                                    })}
                                </Slider>
                    :
                    <>
                        <div className="grid grid-cols-3 gap-y-2 sm:gap-y-2 gap-x-2">
                            {
                                listPfCategories.map((category, index) => {
                                    return (
                                        <div key={category.id} onClick={() => toLink(category)}
                                             className={`rounded-[12px] overflow-hidden  ${index === 0 ? 'col-span-2' : ''}`}>
                                            { 
                                                category.mobile_image ? <img
                                                        src={`${publicRuntimeConfig.magentoImageUrl}${compressImage(category.mobile_image, 400, 188)}`}
                                                        alt="category-image"
                                                        loading='lazy'
                                                        width={index === 0 ? 400 : 230}
                                                        height={index === 0 ? 188 : 220}
                                                        />
                                                    : <div className="mx-auto">
                                                        <Image
                                                            src={ImgPlaceHolder}
                                                            className="w-full h-full cursor-pointer"
                                                            alt='image-placeholder'
                                                        />
                                                    </div>
                                            }
                                        </div>)
                                })
                            }
                        </div>
                        {
                            categories?.length > 8 ? <div className="mt-2">
                                <button
                                    onClick={()=>{
                                        document.querySelector('html').style.overflow = 'hidden'
                                        setShowSort(true)
                                    }}
                                    className={`border border-hyugapurple-500 text-hyugapurple-500 rounded-[8px] text-lg w-full px-3 sm:px-3 py-1.5 font-semibold transition sm:text-[14px]`}
                                >
                                    View All
                                </button>
                            </div> : ''
                        }

                    </>
            }


            {
                screen.device === 'mobile' ? <div className='mobile-facet-container'>

                    {showSort && <div className='fixed left-0 z-10 top-0 h-full w-full bg-[rgb(0,0,0,0.6)]' onClick={hideSort}></div>}
                    <div className={`transition h-[75vh] fixed left-0  bottom-0 z-10 w-full ${showSort ? 'translate-y-0' : 'translate-y-[100%]'} ease-in-out duration-300 shadow-[0_1px_28px_rgba(47,47,47,0.1)]`}>
                        <div className='mobile-sortby-container h-full bg-white rounded-t-xl border-gray-200 border shadow-md'>
                            <div className='mobile-sort-title px-4 py-5 text-lg text-gray-100 border-b font-semibold flex justify-between'>
                                <span>All Category</span>
                                <XIcon
                                    onClick={hideSort}
                                    className="h-6 w-6 text-gray-100 mr-2 pr-1"
                                    size={12}
                                    aria-hidden="true"
                                />
                            </div>
                            <div className='mobile-shop-by-options overflow-y-auto px-4 py-4 '>
                                <h2 className="text-lg font-semibold text-gray-100 mb-3">Shop By Category</h2>

                                <div className="grid grid-cols-3 gap-2">
                                    {
                                        listPfCategories.map((category, index) => {
                                            return (
                                                <div key={category.id} onClick={() => toLink(category)}
                                                     className={`rounded-[12px] overflow-hidden ${index === 0 ? 'col-span-2' : ''}`}>
                                                    {
                                                        category.mobile_image ? <img
                                                                src={`${publicRuntimeConfig.magentoImageUrl}${compressImage(category.mobile_image, 400, 230)}`}
                                                                alt="category-image"
                                                                loading='lazy'
                                                                width={index === 0 ? 400 : 230}
                                                                height={index === 0 ? 188 : 220}
                                                            />
                                                            : <div className="h-[104px] w-[230px] mx-auto">
                                                                <Image
                                                                    src={ImgPlaceHolder}
                                                                    className="w-full h-full cursor-pointer"
                                                                    alt='image-placeholder'
                                                                />
                                                            </div>
                                                    }
                                                </div>)
                                        })
                                    }
                                </div>
                            </div>
                        </div>
                    </div>

                </div> : ''
            }


        </div>
        </div>
        </div> : null}
        </div>
    );
};

export default ShopByCategory;