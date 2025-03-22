import React, {useEffect, useState} from 'react';
import useResize from "hooks/useResize";
import {topDealsSliderSettings} from "shared/components/ProductSlider/constants";
import Slider from "@ant-design/react-slick";
import {catchErrors, compressImage} from "utils/index";
import {fetchCategoryTopDeals} from "modules/ProductListing/redux/reducer";
import {useDispatch} from "react-redux";
import Skeleton from "react-loading-skeleton";
import Image from "next/image";
import getConfig from "next/config";
import {useRouter} from "next/router";
import { featureImpressionTracker } from 'analytics/trackers/impressions';
import { featureImpressionWebEngage } from 'analytics/plugins/webEngage/impressions';
import { useInView } from 'react-intersection-observer';
import { featureImpressionDataLayer } from 'analytics/datalayer/impressions';
import Link from 'next/link';
import { interactionTracker } from 'analytics/trackers/navigate';
import { interactionDataLayer } from 'analytics/datalayer/navigate';
import { trackInteraction } from 'analytics/plugins/webEngage/navigate';
import { phFeatureImpression } from 'analytics/plugins/postHog/impressions';

const {publicRuntimeConfig} = getConfig();

const TopDeals = ({slug, horizontalLine, position, isMobile}) => {
    const dispatch = useDispatch()
    const router = useRouter()
    const screen = useResize()

    const [loading, setLoading] = useState(true)
    const [topDeals, setTopDeals] = useState([])
    const [title, setTitle] = useState('')


    useEffect(() => {
        console.log('fetching slug topdeals', slug)
        fetchTopDeals()
    }, [slug])

    const fetchTopDeals = async () => {
        try {
            const data = {
                method: "get",
                url: `/content/banners/${slug}/category-top-brand-deals`,
            };

            const resp = await dispatch(fetchCategoryTopDeals(data)).unwrap();

            if (!resp.data.status) {
                // catchErrors(null, resp.data.message)
                return;
            }

            setTitle(resp.data.data.title ?? resp.data.data.name ?? 'Top Brands')

            if(window.innerWidth < 475) {
                setTopDeals(resp.data.data.banners.m_web.slice(0, 4));
              } else {
                setTopDeals(resp.data.data.banners.web.slice(0, 4));
              } 

        } catch (e) {
            setTopDeals([])
            // catchErrors(e);
        } finally {
            setLoading(false)
        }
    }

    const toLink = (deal, idx) => {
        const trackingData = {
            id: "top-brands",
            value: deal?.action_url,
            position: idx
        };
        interactionTracker({ action: "content-block-click", entity: trackingData});
        trackInteraction({ action: "content-block-click", entity: trackingData});
        interactionDataLayer("content-block-click", trackingData);
        router.push(`${deal?.action_url}`);
    }

    const trackingData = {
        position: position,
        entity: {
            id: slug,
            type: "top-deals",
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
        {(horizontalLine && topDeals?.length !== 0) && <div className="w-full bg-[#F4F4F9] h-2 flex sm:hidden mb-4" />}
        {
                loading ? <div className="mx-auto max-w-7xl px-4 lg:px-0 mb-5">
                   <Skeleton className="min-w-[150px] max-w-[50px] col-span-1 mb-2" count={1} />
                        <Skeleton
                            containerClassName="grid grid-cols-2 sm:grid-cols-4 gap-[1rem]"
                            inline={true}
                            count={4}
                            width={screen.device === 'mobile' ? 182 : 294}
                            height={screen.device === 'mobile' ? 100 : 220}
                        />{" "}
                    </div> :
        <div className="px-4 lg:px-0 mb-5">
            <h2 className="text-lg md:text-4xl font-semibold tracking-tight text-gray-100 mb-4">{title}</h2>
                   <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-[1rem]">
                       {(topDeals && topDeals?.length !== 0) && topDeals?.map((deal, i) => {
                           return (
                                <div key={i} onClick={() => toLink(deal, i)} className="flex justify-center rounded-[12px] overflow-hidden cursor-pointer">
                                        <img 
                                            src={`${publicRuntimeConfig.magentoImageUrl}/banner/feature${compressImage(deal?.url, 420, 320)}`}
                                            alt="brand-image"
                                            width={320}
                                            loading="lazy"
                                            height={isMobile ? 200 : 220}
                                            className='rounded-xl'
                                        />
                            </div>
                           );
                       })}
                   </div>
        </div>}
        </div>
    );
};

export default TopDeals;