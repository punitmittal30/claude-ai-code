import Slider from '@ant-design/react-slick'
import axios from 'axios';
import useResize from 'hooks/useResize'
import getConfig from 'next/config'
import Image from 'next/image'
import { useRouter } from 'next/router';
import { useEffect, useState } from 'react';
import Skeleton from 'react-loading-skeleton';
import { compressImage } from 'utils/index';
const {publicRuntimeConfig} = getConfig();

const Infographics = ({ isBrand, isBenefits, isHealthStack }) => {
    const screen = useResize()
    const router = useRouter()
    const [infoData, setInfoData] = useState([])
    const [loading, setLoading] = useState(true)

    const infographicSliderSettings = {
        swipeToSlide: true,
        dots: true,
        infinite: false,
        slidesToShow: 1,
        slidesToScroll: 1,
        initialSlide: 0,
        infinite: true
    }

    const fetchInfoGraphics = async (slug) => {
        try {
            const data = {
                method: "get",
                url: `/content/banners/${slug}/category-infographics`,
            };
            const resp = await axios.get(`${publicRuntimeConfig.contentServiceUrl}${data.url}`)
            if (!resp?.data?.status) {
                setLoading(false)
                setInfoData([])
                return;
            }
    
            if(window.innerWidth < 475) {
                setInfoData(resp?.data?.data?.banners?.m_web?.slice(0, 4));
              } else {
                setInfoData(resp?.data?.data?.banners?.web?.slice(0, 1));
              } 
              setLoading(false)
    
        } catch (e) {
            setLoading(false)
          console.log('error in catch', e)
            setInfoData([])
        } finally {
            setLoading(false)
        }
    }

    useEffect(() => {
        fetchInfoGraphics((isBrand || isBenefits || isHealthStack)
        ? router?.query?.slug : router.query.slug[router.query.slug.length - 1])
    }, [router.query.slug])

  return (
    <>
          {loading ?
          <Skeleton width="100%" inline={true} count={1} className="h-[230px] sm:h-[212px] mb-2 hidden" /> : 
           (infoData && infoData?.length !== 0)? <div className='infographics-container overflow-x-hidden'>
              <div className="px-4 lg:px-0 mb-3 sm:mb-4">
                  {screen.device === 'mobile' ? <div className='infographic-slider'>
                      <Slider {...infographicSliderSettings}>
                          {(infoData?.length !== 0) && infoData.map((banner, index) => <div key={index} className='infographic-img-container'>
                              <img
                                  src={`${publicRuntimeConfig.magentoImageUrl}/banner/feature${compressImage(banner?.url, 400, 190)}`}
                                  width={400}
                                  height={190}
                                  loading={index == 0 ? 'eager' :'lazy'}
                                  alt={'info-slider-images'}
                              />
                          </div>)}
                      </Slider> </div> :
                      <div className='infographic-banner'>
                          <div className='infographic-banner-image'>
                              <img
                                  src={`${publicRuntimeConfig.magentoImageUrl}/banner/feature${compressImage(infoData[0]?.url, 1470, 250)}`}
                                  width={1440}
                                  height={250}
                                  alt="infographics-image"
                              />
                          </div>
                      </div>
                  }
              </div>
          </div> : null}
    </>
  )
}

export default Infographics