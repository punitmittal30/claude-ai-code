import useResize from 'hooks/useResize';
import Link from 'next/link';
import React, { useEffect, useState } from 'react';
import { interactionDataLayer } from 'analytics/datalayer/navigate';
import { trackInteraction } from 'analytics/plugins/webEngage/navigate';
import { compressImage } from 'utils/index';
import TopCategoriesIcon from "public/assets/images/TrendingIcon.svg";
import HealthTipIcon from "public/assets/images/HealthTipIcon.svg";
import ReadMoreRightIcon from "public/assets/images/ReadMoreRightIcon.svg"
import Image from "next/image";

const TopCategoriesWidget = ({ data }) => {
    const screen = useResize()
    const [content, setContent] = useState([])
    if (content && content.length) content.sort((a, b) => parseInt(a.priority) - parseInt(b.priority));
    useEffect(() => {
        if (data?.banners?.m_web?.length !== 0 && window.innerWidth < 475) {
            setContent(data?.banners?.m_web);
        } else if (data?.banners?.web?.length !== 0) {
            setContent(data?.banners?.web);
        } else {
            setContent([])
        }
    }, []);

    const trackClick = (i, el) => {
        interactionDataLayer("top-categories-widget-click", {
            name: data?.title ?? undefined,
            position: i,
            value: el?.action_url ?? undefined,
            id: data?.priority ?? undefined
        });
        trackInteraction({
            action: "top-categories-widget-click",
            entity: {
                name: data?.title ?? undefined,
                position: i,
                value: el?.action_url ?? undefined,
                id: data?.priority ?? undefined
            },
        });
    }

    return (
        <>
            {content && content?.length !== 0 ? <div className='health-in-focus-container'>
                {
                    screen.device === 'mobile'
                        ? <div className={`overflow-hidden flex flex-col bg-[#016173] pt-2`}>
                            {data?.title ? <div className='flex text-center'> <p title={data?.title} className='text-base leading-6 cursor-pointer mb-2 text-white text-left font-semibold line-clamp-2 pt-2.5 pl-4 pr-2'>{data?.title} </p> <Image src={TopCategoriesIcon} className='pt-4'></Image></div> : null}
                            <div className="horizontal-scroll flex overflow-scroll pl-4 mb-5 pt-3">
                                {content?.map((el, i) => <div key={i} className='slider-horizontal-scroll pr-[16px] max-w-[152px]'>
                                    <Link href={el?.action_url ? el?.action_url : ''} passHref>
                                        <a onClick={() => trackClick(i, el)}>
                                            <img
                                                loading={"lazy"}
                                                alt={`${el?.title}`}
                                                className="w-full cursor-pointer rounded-xl w-[152px] h-[184px]"
                                                src={compressImage(el?.image_url)}
                                                width={152}
                                                height={184}
                                            />
                                        </a>
                                    </Link>
                                </div>)}
                            </div>
                            {data?.sub_title
                                ? <a href={data?.description}>
                                    <div className='flex text-center bg-[#094C59] !mb-5 ml-4 mr-4 p-2 rounded-xl'>
                                        <Image src={HealthTipIcon} className='!min-w-[48px] flex-none'>
                                        </Image>
                                        <div className='flex-1 text-[14px] cursor-pointer mb-2 text-white text-left font-normal pt-2.5 pl-2 leading-4 pr-[2px]'> {data?.sub_title}
                                        </div>
                                        <Image src={ReadMoreRightIcon}>
                                        </Image>
                                    </div>
                                </a>
                                : null
                            }
                        </div>
                        : <div className={`overflow-hidden flex flex-col bg-[#016173] mt-2 rounded-xl mb-5 pt-1`}>
                            {data?.title ? <div className='flex text-center pt-1'><p title={data?.title} className='text-lg cursor-pointer mb-2 text-white text-left font-semibold line-clamp-2 pt-2 pl-4 pr-2'>{data?.title}</p> <Image src={TopCategoriesIcon} className='pt-4'></Image></div> : null}
                            <div className="horizontal-scroll flex overflow-scroll pl-4 pb-5 pt-2">
                                {data?.sub_title
                                    ? <a href={data?.description}>
                                        <div className='slider-horizontal-scroll mr-6 rounded-xl pt-4 p-2 max-w-[200px] bg-[#094C59] min-h-[248px]'>
                                            <Image src={HealthTipIcon} width={65} height={40}>
                                            </Image>
                                            <p className='flex-1 text-base cursor-pointer mb-2 mt-1 text-white text-left font-normal pl-2 leading-6 pr-[2px]'>{data?.sub_title}
                                            </p>
                                            <div className='flex text-center'>
                                                <p className='text-white font-normal pl-2 mr-2'>{"Read More"}
                                                </p>
                                                <Image src={ReadMoreRightIcon} className='!mt-[1px]'>
                                                </Image>
                                            </div>
                                        </div>
                                    </a>
                                    : null
                                }
                                {content?.map((el, i) => <div key={i} className='slider-horizontal-scroll pr-[16px] max-w-[200px]'>
                                    <Link href={el?.action_url ? el?.action_url : ''} passHref>
                                        <a onClick={() => trackClick(i, el)}>
                                            <img
                                                loading={"lazy"}
                                                alt={`${el?.title}`}
                                                className="w-full cursor-pointer rounded-xl w-[200px] h-[248px] pr-2"
                                                src={compressImage(el?.image_url)}
                                                width={200}
                                                height={248}
                                            />
                                        </a>
                                    </Link>
                                </div>)}
                            </div>
                        </div>
                }
            </div> : null}
        </>
    )
}

export default TopCategoriesWidget;